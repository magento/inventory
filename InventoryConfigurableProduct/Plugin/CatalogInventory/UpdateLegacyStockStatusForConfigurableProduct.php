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
use Magento\InventoryConfigurableProduct\Model\Inventory\ChangeParentProductStockStatus;
use Magento\InventoryConfigurableProduct\Model\IsProductSalableCondition\IsConfigurableProductChildrenSalable;
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
     * @var GetLegacyStockItem
     */
    private $getLegacyStockItem;

    /**
     * @var IsConfigurableProductChildrenSalable
     */
    private $isConfigurableProductChildrenSalable;

    /**
     * @var ChangeParentProductStockStatus
     */
    private $changeParentProductStockStatus;

    /**
     * @param GetProductTypeById $getProductTypeById
     * @param SetDataToLegacyStockStatus $setDataToLegacyStockStatus
     * @param GetSkusByProductIdsInterface $getSkusByProductIds
     * @param GetLegacyStockItem $getLegacyStockItem
     * @param IsConfigurableProductChildrenSalable $isConfigurableProductChildrenSalable
     * @param ChangeParentProductStockStatus $changeParentProductStockStatus
     */
    public function __construct(
        ChangeParentProductStockStatus $changeParentProductStockStatus,
        GetProductTypeById $getProductTypeById,
        SetDataToLegacyStockStatus $setDataToLegacyStockStatus,
        GetSkusByProductIdsInterface $getSkusByProductIds,
        GetLegacyStockItem $getLegacyStockItem,
        IsConfigurableProductChildrenSalable $isConfigurableProductChildrenSalable
    ) {
        $this->changeParentProductStockStatus = $changeParentProductStockStatus;
        $this->getProductTypeById = $getProductTypeById;
        $this->setDataToLegacyStockStatus = $setDataToLegacyStockStatus;
        $this->getSkusByProductIds = $getSkusByProductIds;
        $this->getLegacyStockItem = $getLegacyStockItem;
        $this->isConfigurableProductChildrenSalable = $isConfigurableProductChildrenSalable;
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
        $stockProductId = $stockItem->getProductId();
        if ($stockItem->getIsInStock() &&
            $this->getProductTypeById->execute($stockProductId) === Configurable::TYPE_CODE
        ) {
            $productSku = $this->getSkusByProductIds
                ->execute([$stockProductId])[$stockItem->getProductId()];

            if ($stockItem->getStockStatusChangedAuto() ||
                ($this->stockStatusChange($productSku)
                    && $this->isConfigurableProductChildrenSalable->execute($productSku, Stock::DEFAULT_STOCK_ID)
                )
            ) {
                $this->setDataToLegacyStockStatus->execute(
                    $productSku,
                    (float) $stockItem->getQty(),
                    Stock::STOCK_IN_STOCK
                );
            }
        }
        $this->changeParentProductStockStatus->execute($stockItem->getProductId());

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
}
