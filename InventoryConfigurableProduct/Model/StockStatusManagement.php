<?php
/************************************************************************
 *
 * Copyright 2023 Adobe
 * All Rights Reserved.
 *
 * NOTICE: All information contained herein is, and remains
 * the property of Adobe and its suppliers, if any. The intellectual
 * and technical concepts contained herein are proprietary to Adobe
 * and its suppliers and are protected by all applicable intellectual
 * property laws, including trade secret and copyright laws.
 * Dissemination of this information or reproduction of this material
 * is strictly forbidden unless prior written permission is obtained
 * from Adobe.
 * ************************************************************************
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurableProduct\Model;

use Magento\CatalogInventory\Model\Stock\Item as StockItem;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryCatalogApi\Model\GetSkusByProductIdsInterface;
use Magento\InventoryConfiguration\Model\GetLegacyStockItemsInterface;

class StockStatusManagement
{
    /**
     * @param GetSkusByProductIdsInterface $getSkusByProductIds
     * @param GetLegacyStockItemsInterface $getLegacyStockItems
     * @param Configurable $configurableType
     */
    public function __construct(
        private readonly GetSkusByProductIdsInterface $getSkusByProductIds,
        private readonly GetLegacyStockItemsInterface $getLegacyStockItems,
        private readonly Configurable $configurableType
    ) {
    }

    /**
     * Verify stock status for configurable product based on children stock status.
     *
     * @param array $productIds
     * @return bool
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function isAnyProductInStock(array $productIds): bool
    {
        $isInStock = false;
        if (!empty($productIds)) {
            $skus = $this->getSkusByProductIds->execute($productIds);
            foreach ($this->getLegacyStockItems->execute($skus) as $childStockItem) {
                if ($childStockItem->getIsInStock()) {
                    $isInStock = true;
                    break;
                }
            }
        }
        return $isInStock;
    }

    /**
     * Update configurable stock status.
     *
     * Updates is_in_stock and stock_status_changed_auto according to children stock status.
     * This method does not save changes in db.
     *
     * @param StockItem $stockItem
     * @return StockItem
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function update(StockItem $stockItem): StockItem
    {
        $childrenIds = $this->configurableType->getChildrenIds($stockItem->getProductId());
        $childrenIds = array_shift($childrenIds);
        if (empty($childrenIds)) {
            return $stockItem;
        }
        $isInStock = $this->isAnyProductInStock($childrenIds);
        if ($isInStock) {
            if ($stockItem->isObjectNew()) {
                $stockItem->setStockStatusChangedAuto((int)$stockItem->getIsInStock());
            } else {
                $sku = $this->getSkusByProductIds->execute([$stockItem->getProductId()])[$stockItem->getProductId()];
                $existingStockItem = current($this->getLegacyStockItems->execute([$sku]));
                if ($existingStockItem) {
                    if ($existingStockItem->getIsInStock() !== $stockItem->getIsInStock()) {
                        $stockItem->setStockStatusChangedAuto(0);
                    }
                } else {
                    $stockItem->setStockStatusChangedAuto((int)$stockItem->getIsInStock());
                }
            }

            if ($stockItem->getIsInStock() === false && $stockItem->getStockStatusChangedAuto()) {
                $stockItem->setIsInStock(true);
            }
        } else {
            if ($stockItem->getIsInStock() === true) {
                $stockItem->setIsInStock(false);
                $stockItem->setStockStatusChangedAuto(1);
            }
        }
        return $stockItem;
    }
}
