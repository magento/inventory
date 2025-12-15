<?php
/**
 * Copyright 2025 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Indexer;

use Magento\InventoryApi\Model\GetStockIdsBySkusInterface;
use Magento\InventoryCatalogApi\Model\CompositeProductTypesProviderInterface;
use Magento\InventoryCatalogApi\Model\GetChildrenSkusOfParentSkusInterface;
use Magento\InventoryCatalogApi\Model\GetProductTypesBySkusInterface;
use Magento\InventoryIndexer\Indexer\SourceItem\SkuListInStockFactory;
use Magento\InventoryIndexer\Indexer\Stock\SkuListsProcessor;

class CompositeProductsIndexer
{
    /**
     * @param CompositeProductTypesProviderInterface $compositeProductTypesProvider
     * @param GetProductTypesBySkusInterface $getProductTypesBySkus
     * @param GetChildrenSkusOfParentSkusInterface $getChildrenSkusOfParentSkus
     * @param GetStockIdsBySkusInterface $getStockIdsBySkus
     * @param SkuListInStockFactory $skuListInStockFactory
     * @param SkuListsProcessor $skuListsProcessor
     */
    public function __construct(
        private readonly CompositeProductTypesProviderInterface $compositeProductTypesProvider,
        private readonly GetProductTypesBySkusInterface $getProductTypesBySkus,
        private readonly GetChildrenSkusOfParentSkusInterface $getChildrenSkusOfParentSkus,
        private readonly GetStockIdsBySkusInterface $getStockIdsBySkus,
        private readonly SkuListInStockFactory $skuListInStockFactory,
        private readonly SkuListsProcessor $skuListsProcessor,
    ) {
    }

    /**
     * Reindex composite products present in the provided SKU list for stocks related to their child products.
     *
     * @param string[] $skus
     * @return void
     */
    public function reindexList(array $skus): void
    {
        if (!$skus) {
            return;
        }

        $productTypesBySkus = $this->getProductTypesBySkus->execute($skus);
        $productSkusByTypes = array_fill_keys(array_unique(array_values($productTypesBySkus)), []);
        foreach ($productTypesBySkus as $sku => $type) {
            $productSkusByTypes[$type][] = (string) $sku;
        }
        $compositeTypes = $this->compositeProductTypesProvider->execute();
        $compositeSkusByTypes = array_intersect_key($productSkusByTypes, array_flip($compositeTypes));
        $compositeSkus = array_merge([], ...array_values($compositeSkusByTypes));
        if (!$compositeSkus) {
            return;
        }

        $parentStocks = [];
        $childrenSkusOfParentSkus = $this->getChildrenSkusOfParentSkus->execute($compositeSkus);
        foreach ($childrenSkusOfParentSkus as $parentSku => $childrenSkus) {
            if (!$childrenSkus) {
                continue;
            }

            $stockIds = $this->getStockIdsBySkus->execute($childrenSkus);
            foreach ($stockIds as $stockId) {
                $parentStocks[$stockId][] = (string) $parentSku;
            }
        }

        $skuListInStockList = [];
        foreach ($parentStocks as $stockId => $parentSkus) {
            $skuListInStockList[] = $this->skuListInStockFactory->create(
                ['stockId' => $stockId, 'skuList' => $parentSkus]
            );
        }
        $this->skuListsProcessor->reindexList($skuListInStockList);
    }
}
