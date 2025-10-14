<?php
/**
 * Copyright 2025 Adobe
 * All Rights Reserved.
 */

declare(strict_types=1);

namespace Magento\InventoryImportExport\Plugin\Import;

use Magento\CatalogImportExport\Model\StockItemProcessorInterface;
use Magento\InventoryConfiguration\Model\LegacyStockItem\CacheStorage;

class PurgeLegacyStockItemCacheStorage
{
    /**
     * @param CacheStorage $stockItemCacheStorage
     */
    public function __construct(
        private readonly CacheStorage $stockItemCacheStorage,
    ) {
    }

    /**
     * After plugin for StockItemProcessor::process to clean up legacy stock item cache storage
     *
     * @param StockItemProcessorInterface $subject
     * @param mixed $result
     * @param array $stockData
     * @param array $importedData
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterProcess(
        StockItemProcessorInterface $subject,
        mixed $result,
        array $stockData,
        array $importedData
    ): void {
        foreach (array_keys($stockData) as $sku) {
            $this->stockItemCacheStorage->delete((string)$sku);
        }
    }
}
