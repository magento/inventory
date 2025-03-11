<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Plugin\CatalogInventory;

use Exception;
use Magento\CatalogInventory\Model\ResourceModel\Stock\Item as ItemResourceModel;
use Magento\CatalogInventory\Model\Stock\Item;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Model\AbstractModel;
use Magento\InventoryCatalog\Model\GetDefaultSourceItemBySku;
use Magento\InventoryCatalog\Model\UpdateDefaultStock;
use Magento\InventoryCatalog\Model\UpdateSourceItemBasedOnLegacyStockItem;
use Magento\InventoryCatalogApi\Model\CompositeProductStockStatusProcessorInterface;
use Magento\InventoryCatalogApi\Model\GetProductTypesBySkusInterface;
use Magento\InventoryCatalogApi\Model\GetSkusByProductIdsInterface;
use Magento\InventoryCatalogApi\Model\IsSingleSourceModeInterface;
use Magento\InventoryConfiguration\Model\LegacyStockItem\CacheStorage;
use Magento\InventoryConfigurationApi\Model\IsSourceItemManagementAllowedForProductTypeInterface;
use Magento\InventoryIndexer\Model\ProductSalabilityChangeProcessorInterface;

/**
 * Class provides around Plugin on \Magento\CatalogInventory\Model\ResourceModel\Stock\Item::save
 * to update data in Inventory source item based on legacy Stock Item data
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class UpdateSourceItemAtLegacyStockItemSavePlugin
{
    /**
     * @var int
     */
    private int $recursionLevel = 0;

    /**
     * @param UpdateSourceItemBasedOnLegacyStockItem $updateSourceItemBasedOnLegacyStockItem
     * @param ResourceConnection $resourceConnection
     * @param IsSourceItemManagementAllowedForProductTypeInterface $isSourceItemManagementAllowed
     * @param GetProductTypesBySkusInterface $getProductTypeBySku
     * @param GetSkusByProductIdsInterface $getSkusByProductIds
     * @param GetDefaultSourceItemBySku $getDefaultSourceItemBySku
     * @param CacheStorage $stockItemCacheStorage
     * @param UpdateDefaultStock $updateDefaultStock
     * @param ProductSalabilityChangeProcessorInterface $productSalabilityChangeProcessor
     * @param CompositeProductStockStatusProcessorInterface $compositeProductStockStatusProcessor
     * @param IsSingleSourceModeInterface $isSingleSourceMode
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        private readonly UpdateSourceItemBasedOnLegacyStockItem $updateSourceItemBasedOnLegacyStockItem,
        private readonly ResourceConnection $resourceConnection,
        private readonly IsSourceItemManagementAllowedForProductTypeInterface $isSourceItemManagementAllowed,
        private readonly GetProductTypesBySkusInterface $getProductTypeBySku,
        private readonly GetSkusByProductIdsInterface $getSkusByProductIds,
        private readonly GetDefaultSourceItemBySku $getDefaultSourceItemBySku,
        private readonly CacheStorage $stockItemCacheStorage,
        private readonly UpdateDefaultStock $updateDefaultStock,
        private readonly ProductSalabilityChangeProcessorInterface $productSalabilityChangeProcessor,
        private readonly CompositeProductStockStatusProcessorInterface $compositeProductStockStatusProcessor,
        private readonly IsSingleSourceModeInterface $isSingleSourceMode
    ) {
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
            /**
             * @var Item $legacyStockItem
             */
            $subject->setProcessIndexEvents(false);
            try {
                // need to save configuration
                $proceed($legacyStockItem);
            } finally {
                $subject->setProcessIndexEvents(true);
            }

            $productId = $legacyStockItem->getProductId();
            $sku = $this->getSkusByProductIds->execute([$productId])[$productId];
            $typeId = $this->getProductTypeBySku->execute([$sku])[$sku];

            $this->stockItemCacheStorage->delete($sku);

            if ($this->isSourceItemManagementAllowed->execute($typeId)
                && $this->shouldAlignDefaultSourceWithLegacy($legacyStockItem)
            ) {
                $this->updateSourceItemBasedOnLegacyStockItem->execute($legacyStockItem);
            }
            $affectedSkus = $this->updateDefaultStock->execute([$sku]);
            if ($affectedSkus) {
                $subject->addCommitCallback(
                    function () use ($affectedSkus) {
                        $this->productSalabilityChangeProcessor->execute($affectedSkus);
                    }
                );
            }
            try {
                // Prevent recursion.
                // This should never happen as composite products cannot have composite products as children.
                $this->recursionLevel++;
                if ($this->recursionLevel === 1 && $this->isSingleSourceMode->execute()) {
                    $this->compositeProductStockStatusProcessor->execute([$sku]);
                }
            } finally {
                $this->recursionLevel--;
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
}
