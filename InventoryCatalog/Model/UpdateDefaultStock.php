<?php
/************************************************************************
 *
 * Copyright 2024 Adobe
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

namespace Magento\InventoryCatalog\Model;

use Magento\CatalogInventory\Model\Indexer\Stock\Processor as StockIndexProcessor;
use Magento\CatalogInventory\Model\Stock;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryCatalog\Model\ResourceModel\SetDataToLegacyStockStatus;
use Magento\InventoryCatalogApi\Model\GetProductIdsBySkusInterface;
use Magento\InventoryConfiguration\Model\GetLegacyStockItemsInterface;
use Magento\InventoryConfigurationApi\Model\IsSourceItemManagementAllowedForSkuInterface;
use Magento\InventorySalesApi\Api\AreProductsSalableInterface;
use Magento\InventorySalesApi\Model\GetStockItemDataInterface;

class UpdateDefaultStock
{
    /**
     * @param AreProductsSalableInterface $areProductsSalable
     * @param GetStockItemDataInterface $getStockItemData
     * @param GetProductIdsBySkusInterface $getProductIdsBySkus
     * @param StockIndexProcessor $stockIndexProcessor
     * @param SetDataToLegacyStockStatus $setDataToLegacyStockStatus
     * @param GetLegacyStockItemsInterface $getLegacyStockItems
     * @param IsSourceItemManagementAllowedForSkuInterface $isSourceItemManagementAllowed
     */
    public function __construct(
        private readonly AreProductsSalableInterface $areProductsSalable,
        private readonly GetStockItemDataInterface $getStockItemData,
        private readonly GetProductIdsBySkusInterface $getProductIdsBySkus,
        private readonly StockIndexProcessor $stockIndexProcessor,
        private readonly SetDataToLegacyStockStatus $setDataToLegacyStockStatus,
        private readonly GetLegacyStockItemsInterface $getLegacyStockItems,
        private readonly IsSourceItemManagementAllowedForSkuInterface $isSourceItemManagementAllowed
    ) {
    }

    /**
     * Update default stock index and return affected skus.
     *
     * @param string[] $skus
     * @return string[]
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function execute(array $skus): array
    {
        if (empty($skus)) {
            return [];
        }
        $idsBySku = $this->getProductIdsBySkus->execute($skus);
        $skusById = array_flip($idsBySku);
        $affectedSkus = [];
        $isProductSalableResults = [];
        foreach ($this->areProductsSalable->execute($skus, Stock::DEFAULT_STOCK_ID) as $isProductSalableResult) {
            $isProductSalableResults[$isProductSalableResult->getSku()] = $isProductSalableResult;
        }
        foreach ($this->getLegacyStockItems->execute($skus) as $stockItem) {
            $sku = (string) $skusById[$stockItem->getProductId()];
            $stockItemData = $this->getStockItemData->execute($sku, Stock::DEFAULT_STOCK_ID);
            $statusBefore = $stockItemData !== null && $stockItemData[GetStockItemDataInterface::IS_SALABLE];
            $statusAfter = $isProductSalableResults[$sku]->isSalable();
            if ($this->isSourceItemManagementAllowed->execute($sku)) {
                $this->setDataToLegacyStockStatus->execute(
                    $sku,
                    (float)$stockItem->getQty(),
                    (int)$statusAfter
                );
            }
            if ($statusBefore !== $statusAfter) {
                $affectedSkus[] = $sku;
            }
        }
        if (!empty($idsBySku)) {
            $this->stockIndexProcessor->reindexList(array_values($idsBySku));
        }
        return $affectedSkus;
    }
}
