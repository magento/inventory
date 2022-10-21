<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurableProduct\Plugin\CatalogInventory;

use Magento\Catalog\Model\ResourceModel\GetProductTypeById;
use Magento\CatalogInventory\Model\ResourceModel\Stock\Item as ItemResourceModel;
use Magento\Framework\Model\AbstractModel as StockItem;
use Magento\InventoryCatalog\Model\ResourceModel\SetDataToLegacyStockStatus;
use Magento\InventoryCatalogApi\Model\GetSkusByProductIdsInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\CatalogInventory\Model\Stock;
use Magento\InventorySalesApi\Api\AreProductsSalableInterface;
use Magento\InventoryConfiguration\Model\GetLegacyStockItem;

/**
 * Class provides after Plugin on Magento\CatalogInventory\Model\ResourceModel\Stock\Item::save
 * to update legacy stock status for configurable product
 */
class UpdateLegacyStockStatusForConfigurableProduct
{
    /**
     * @var GetProductTypeById
     */
    private $getProductTypeById;

    /**
     * @var SetDataToLegacyStockStatus
     */
    private $setDataToLegacyStockStatus;

    /**
     * @var GetSkusByProductIdsInterface
     */
    private $getSkusByProductIds;

    /**
     * @var Configurable
     */
    private $configurableType;

    /**
     * @var AreProductsSalableInterface
     */
    private $areProductsSalable;

    /**
     * @var GetLegacyStockItem
     */
    private $getLegacyStockItem;

    /**
     * @param GetProductTypeById $getProductTypeById
     * @param SetDataToLegacyStockStatus $setDataToLegacyStockStatus
     * @param GetSkusByProductIdsInterface $getSkusByProductIds
     * @param Configurable $configurableType
     * @param AreProductsSalableInterface $areProductsSalable
     * @param GetLegacyStockItem $getLegacyStockItem
     */
    public function __construct(
        GetProductTypeById $getProductTypeById,
        SetDataToLegacyStockStatus $setDataToLegacyStockStatus,
        GetSkusByProductIdsInterface $getSkusByProductIds,
        Configurable $configurableType,
        AreProductsSalableInterface $areProductsSalable,
        GetLegacyStockItem $getLegacyStockItem
    ) {
        $this->getProductTypeById = $getProductTypeById;
        $this->setDataToLegacyStockStatus = $setDataToLegacyStockStatus;
        $this->getSkusByProductIds = $getSkusByProductIds;
        $this->configurableType = $configurableType;
        $this->areProductsSalable = $areProductsSalable;
        $this->getLegacyStockItem = $getLegacyStockItem;
    }

    /**
     * Update source item for legacy stock of a configurable product
     *
     * @param ItemResourceModel $subject
     * @param ItemResourceModel $result
     * @param StockItem $stockItem
     * @return ItemResourceModel
     * @throws Exception
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterSave(ItemResourceModel $subject, ItemResourceModel $result, StockItem $stockItem)
    {
        if ($stockItem->getIsInStock() &&
            $this->getProductTypeById->execute($stockItem->getProductId()) === Configurable::TYPE_CODE
        ) {
            $productSku = $this->getSkusByProductIds
                ->execute([$stockItem->getProductId()])[$stockItem->getProductId()];

            if ($stockItem->getStockStatusChangedAuto() ||
                ($this->stockStatusChange($productSku) && $this->hasChildrenInStock($stockItem->getProductId()))
            ) {
                $this->setDataToLegacyStockStatus->execute(
                    $productSku,
                    (float) $stockItem->getQty(),
                    Stock::STOCK_IN_STOCK
                );
            }
        }

        return $result;
    }

    /**
     * Checks if configurable product stock item status was changed
     *
     * @param string $sku
     * @return bool
     */
    private function stockStatusChange(string $sku): bool
    {
        return $this->getLegacyStockItem->execute($sku)->getIsInStock() == Stock::STOCK_OUT_OF_STOCK;
    }

    /**
     * Checks if configurable has salable options
     *
     * @param int $productId
     * @return bool
     */
    private function hasChildrenInStock(int $productId): bool
    {
        $childrenIds = $this->configurableType->getChildrenIds($productId);
        if (empty($childrenIds)) {
            return false;
        }
        $skus = $this->getSkusByProductIds->execute(array_shift($childrenIds));
        $areSalableResults = $this->areProductsSalable->execute($skus, Stock::DEFAULT_STOCK_ID);
        foreach ($areSalableResults as $productSalable) {
            if ($productSalable->isSalable() === true) {
                return true;
            }
        }

        return false;
    }
}
