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

namespace Magento\InventoryCatalog\Observer;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\CatalogInventory\Model\Indexer\Stock\Processor as StockIndexProcessor;
use Magento\CatalogInventory\Model\StockItemValidator;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\InventoryCatalog\Model\Cache\ProductIdsBySkusStorage;
use Magento\InventoryCatalog\Model\Cache\ProductSkusByIdsStorage;
use Magento\InventoryCatalog\Model\Cache\ProductTypesBySkusStorage;

/**
 * This class extends the original SaveInventoryDataObserver to invalidate caches and ignore processing of parent stocks
 */
class SaveInventoryDataObserver extends \Magento\CatalogInventory\Observer\SaveInventoryDataObserver
{
    /**
     * @param ProductIdsBySkusStorage $productIdsBySkusStorage
     * @param ProductSkusByIdsStorage $productSkusByIdsStorage
     * @param ProductTypesBySkusStorage $productTypesBySkusStorage
     * @param StockIndexProcessor $stockIndexProcessor
     * @param StockConfigurationInterface $stockConfiguration
     * @param StockRegistryInterface $stockRegistry
     * @param StockItemValidator $stockItemValidator
     */
    public function __construct(
        private readonly ProductIdsBySkusStorage $productIdsBySkusStorage,
        private readonly ProductSkusByIdsStorage $productSkusByIdsStorage,
        private readonly ProductTypesBySkusStorage $productTypesBySkusStorage,
        private readonly StockIndexProcessor $stockIndexProcessor,
        StockConfigurationInterface $stockConfiguration,
        StockRegistryInterface $stockRegistry,
        StockItemValidator $stockItemValidator
    ) {
        // Ignore $parentItemProcessorPool as this logic is moved to a lower level to cover cases
        // when the stock item is saved separately
        // @see \Magento\InventoryCatalog\Plugin\CatalogInventory\UpdateSourceItemAtLegacyStockItemSavePlugin
        parent::__construct($stockConfiguration, $stockRegistry, $stockItemValidator);
    }

    /**
     * @inheritdoc
     */
    public function execute(EventObserver $observer)
    {
        /**
         * @var \Magento\Catalog\Model\Product $product
         */
        $product = $observer->getEvent()->getProduct();
        $productId = (int) $product->getId();
        $sku = (string) $product->getSku();
        // Invalidate caches as the product sku and type could be changed
        // For example, simple product is converted to configurable product if children are added to it
        $this->productTypesBySkusStorage->delete($sku);
        $this->productIdsBySkusStorage->delete($sku);
        $this->productSkusByIdsStorage->delete($productId);

        // Reindex the product if it was disabled and now enabled because disabled products are not indexed
        if (!$product->isObjectNew()
            && (int) $product->getStatus() === Status::STATUS_ENABLED
            && (int) $product->getOrigData(ProductInterface::STATUS) === Status::STATUS_DISABLED
        ) {
            $this->stockIndexProcessor->reindexRow($productId, true);
        }
        parent::execute($observer);
    }
}
