<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Plugin\CatalogInventory;

use Exception;
use Magento\CatalogInventory\Model\ResourceModel\Stock\Item as ItemResourceModel;
use Magento\CatalogInventory\Model\Stock;
use Magento\CatalogInventory\Model\Stock\Item;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Model\AbstractModel;
use Magento\InventoryCatalog\Model\Cache\ProductIdsBySkusStorage;
use Magento\InventoryCatalog\Model\Cache\ProductSkusByIdsStorage;
use Magento\InventoryCatalog\Model\GetDefaultSourceItemBySku;
use Magento\InventoryCatalog\Model\ResourceModel\SetDataToLegacyStockStatus;
use Magento\InventoryCatalog\Model\UpdateSourceItemBasedOnLegacyStockItem;
use Magento\InventoryCatalogApi\Model\GetProductTypesBySkusInterface;
use Magento\InventoryCatalogApi\Model\GetSkusByProductIdsInterface;
use Magento\InventoryConfiguration\Model\LegacyStockItem\CacheStorage;
use Magento\InventoryConfigurationApi\Model\IsSourceItemManagementAllowedForProductTypeInterface;
use Magento\InventorySalesApi\Api\AreProductsSalableInterface;

/**
 * Class provides around Plugin on \Magento\CatalogInventory\Model\ResourceModel\Stock\Item::save
 * to update data in Inventory source item based on legacy Stock Item data
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class UpdateSourceItemAtLegacyStockItemSavePlugin
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var IsSourceItemManagementAllowedForProductTypeInterface
     */
    private $isSourceItemManagementAllowedForProductType;

    /**
     * @var UpdateSourceItemBasedOnLegacyStockItem
     */
    private $updateSourceItemBasedOnLegacyStockItem;

    /**
     * @var GetProductTypesBySkusInterface
     */
    private $getProductTypeBySku;

    /**
     * @var GetSkusByProductIdsInterface
     */
    private $getSkusByProductIds;

    /**
     * @var GetDefaultSourceItemBySku
     */
    private $getDefaultSourceItemBySku;

    /**
     * @var AreProductsSalableInterface
     */
    private $areProductsSalable;

    /**
     * @var SetDataToLegacyStockStatus
     */
    private $setDataToLegacyStockStatus;

    /**
     * @var ProductIdsBySkusStorage
     */
    private $productIdsBySkusStorage;

    /**
     * @var ProductSkusByIdsStorage
     */
    private $productSkusByIdsStorage;

    /**
     * @var CacheStorage
     */
    private $itemCacheStorage;

    /**
     * @param UpdateSourceItemBasedOnLegacyStockItem $updateSourceItemBasedOnLegacyStockItem
     * @param ResourceConnection $resourceConnection
     * @param IsSourceItemManagementAllowedForProductTypeInterface $isSourceItemManagementAllowedForProductType
     * @param GetProductTypesBySkusInterface $getProductTypeBySku
     * @param GetSkusByProductIdsInterface $getSkusByProductIds
     * @param GetDefaultSourceItemBySku $getDefaultSourceItemBySku
     * @param AreProductsSalableInterface $areProductsSalable
     * @param SetDataToLegacyStockStatus $setDataToLegacyStockStatus
     * @param ProductIdsBySkusStorage $productIdsBySkusStorage
     * @param ProductSkusByIdsStorage $productSkusByIdsStorage
     * @param CacheStorage $itemCacheStorage
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        UpdateSourceItemBasedOnLegacyStockItem $updateSourceItemBasedOnLegacyStockItem,
        ResourceConnection $resourceConnection,
        IsSourceItemManagementAllowedForProductTypeInterface $isSourceItemManagementAllowedForProductType,
        GetProductTypesBySkusInterface $getProductTypeBySku,
        GetSkusByProductIdsInterface $getSkusByProductIds,
        GetDefaultSourceItemBySku $getDefaultSourceItemBySku,
        AreProductsSalableInterface $areProductsSalable,
        SetDataToLegacyStockStatus $setDataToLegacyStockStatus,
        ProductIdsBySkusStorage $productIdsBySkusStorage,
        ProductSkusByIdsStorage $productSkusByIdsStorage,
        CacheStorage $itemCacheStorage
    ) {
        $this->updateSourceItemBasedOnLegacyStockItem = $updateSourceItemBasedOnLegacyStockItem;
        $this->resourceConnection = $resourceConnection;
        $this->isSourceItemManagementAllowedForProductType = $isSourceItemManagementAllowedForProductType;
        $this->getProductTypeBySku = $getProductTypeBySku;
        $this->getSkusByProductIds = $getSkusByProductIds;
        $this->getDefaultSourceItemBySku = $getDefaultSourceItemBySku;
        $this->areProductsSalable = $areProductsSalable;
        $this->setDataToLegacyStockStatus = $setDataToLegacyStockStatus;
        $this->productIdsBySkusStorage = $productIdsBySkusStorage;
        $this->productSkusByIdsStorage = $productSkusByIdsStorage;
        $this->itemCacheStorage = $itemCacheStorage;
    }

    /**
     * Update source item for legacy stock.
     *
     * @param ItemResourceModel $subject
     * @param callable $proceed
     * @param AbstractModel $legacyStockItem
     * @return ItemResourceModel
     * @throws Exception
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundSave(ItemResourceModel $subject, callable $proceed, AbstractModel $legacyStockItem)
    {
        $connection = $this->resourceConnection->getConnection();
        $connection->beginTransaction();
        try {
            // need to save configuration
            $proceed($legacyStockItem);

            $typeId = $this->getTypeId($legacyStockItem);
            if ($this->isSourceItemManagementAllowedForProductType->execute($typeId)) {
                if ($this->shouldAlignDefaultSourceWithLegacy($legacyStockItem)) {
                    $this->updateSourceItemBasedOnLegacyStockItem->execute($legacyStockItem);
                }

                $productSku = $this->getSkusByProductIds
                    ->execute([$legacyStockItem->getProductId()])[$legacyStockItem->getProductId()];
                $this->updateCaches($legacyStockItem, $productSku);

                $stockStatuses = [];
                $areSalableResults = $this->areProductsSalable->execute([$productSku], Stock::DEFAULT_STOCK_ID);
                foreach ($areSalableResults as $productSalable) {
                    $stockStatuses[$productSalable->getSku()] = $productSalable->isSalable();
                }
                $this->setDataToLegacyStockStatus->execute(
                    $productSku,
                    (float) $legacyStockItem->getQty(),
                    $stockStatuses[(string)$productSku] === true ? 1 : 0
                );
            }

            $connection->commit();

            return $subject;
        } catch (Exception $e) {
            $connection->rollBack();
            throw $e;
        }
    }

    /**
     * Return true if legacy stock item should update default source (if existing)
     *
     * @param Item $legacyStockItem
     * @return bool
     * @throws InputException
     */
    private function shouldAlignDefaultSourceWithLegacy(Item $legacyStockItem): bool
    {
        $productSku = $this->getSkusByProductIds
            ->execute([$legacyStockItem->getProductId()])[$legacyStockItem->getProductId()];

        $result = $legacyStockItem->getIsInStock() ||
            ((float) $legacyStockItem->getQty() !== (float) 0) ||
            ($this->getDefaultSourceItemBySku->execute($productSku) !== null);

        return $result;
    }

    /**
     * Returns product type id.
     *
     * @param Item $legacyStockItem
     * @return string
     * @throws InputException
     */
    private function getTypeId(Item $legacyStockItem): string
    {
        $typeId = $legacyStockItem->getTypeId();
        if ($typeId === null) {
            $sku = $legacyStockItem->getSku();
            if ($sku === null) {
                $productId = $legacyStockItem->getProductId();
                $sku = $this->getSkusByProductIds->execute([$productId])[$productId];
            }
            $typeId = $this->getProductTypeBySku->execute([$sku])[$sku];
        }

        return $typeId;
    }

    /**
     * Update legacy item caches
     *
     * @param Item $legacyStockItem
     * @param string $productSku
     * @return void
     */
    private function updateCaches(Item $legacyStockItem, string $productSku): void
    {
        $this->productIdsBySkusStorage->set(
            $productSku,
            $legacyStockItem->getProductId()
        );
        $this->productSkusByIdsStorage->set(
            (int)$legacyStockItem->getProductId(),
            $productSku
        );
        // Remove cache to get updated legacy stock item on the next request.
        $this->itemCacheStorage->delete($productSku);
    }
}
