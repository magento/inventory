<?php
/**
 * Copyright 2025 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Indexer\Stock;

use ArrayIterator;
use Magento\InventoryCatalogApi\Model\GetProductTypesBySkusInterface;
use Magento\InventoryConfigurationApi\Model\GetAllowedProductTypesForSourceItemManagementInterface;
use Magento\InventoryIndexer\Indexer\SiblingProductsProvidersPool;
use Magento\InventoryIndexer\Indexer\SourceItem\SkuListInStock;
use Magento\InventoryMultiDimensionalIndexerApi\Model\IndexAlias;
use Magento\InventoryMultiDimensionalIndexerApi\Model\IndexHandlerInterface;
use Magento\InventoryMultiDimensionalIndexerApi\Model\IndexName;

class IndexDataFiller
{
    /**
     * @param ReservationsIndexTable $reservationsIndexTable
     * @param PrepareReservationsIndexData $prepareReservationsIndexData
     * @param GetProductTypesBySkusInterface $getProductTypesBySkus
     * @param GetAllowedProductTypesForSourceItemManagementInterface $getSourceItemManagementProductTypes
     * @param DataProvider $dataProvider
     * @param IndexHandlerInterface $indexStructureHandler
     * @param SiblingProductsProvidersPool $siblingProductsProvidersPool
     */
    public function __construct(
        private readonly ReservationsIndexTable $reservationsIndexTable,
        private readonly PrepareReservationsIndexData $prepareReservationsIndexData,
        private readonly GetProductTypesBySkusInterface $getProductTypesBySkus,
        private readonly GetAllowedProductTypesForSourceItemManagementInterface $getSourceItemManagementProductTypes,
        private readonly DataProvider $dataProvider,
        private readonly IndexHandlerInterface $indexStructureHandler,
        private readonly SiblingProductsProvidersPool $siblingProductsProvidersPool,
    ) {
    }

    /**
     * Fill index with data.
     *
     * @param IndexName $indexName
     * @param SkuListInStock $skuListInStock
     * @param string $connectionName
     * @return void
     */
    public function fillIndex(IndexName $indexName, SkuListInStock $skuListInStock, string $connectionName): void
    {
        $stockId = $skuListInStock->getStockId();
        $this->reservationsIndexTable->createTable($stockId);
        $this->prepareReservationsIndexData->execute($stockId);

        $skuList = $skuListInStock->getSkuList();
        if ($skuList) {
            $productTypesBySkus = $this->getProductTypesBySkus->execute($skuList);
            $productSkusByTypes = array_fill_keys(array_unique(array_values($productTypesBySkus)), []);
            foreach ($productTypesBySkus as $sku => $type) {
                $productSkusByTypes[$type][] = $sku;
            }

            $sourceItemManagementTypes = $this->getSourceItemManagementProductTypes->execute();
            $composableSkusByTypes = array_intersect_key($productSkusByTypes, array_flip($sourceItemManagementTypes));
            $compositeSkusByTypes = array_diff_key($productSkusByTypes, $composableSkusByTypes);

            $composableSkus = array_merge([], ...array_values($composableSkusByTypes));
            if ($composableSkus) {
                $data = $this->dataProvider->getData($stockId, $composableSkus);
                $this->indexStructureHandler->cleanIndex(
                    $indexName,
                    new ArrayIterator($composableSkus),
                    $connectionName
                );
                $this->indexStructureHandler->saveIndex($indexName, new ArrayIterator($data), $connectionName);
            }
        } else {
            $data = $this->dataProvider->getData($stockId);
            $this->indexStructureHandler->saveIndex($indexName, new ArrayIterator($data), $connectionName);
            $composableSkus = array_keys($data);
            $compositeSkusByTypes = [];
        }

        $siblingsGroupedByType = $this->siblingProductsProvidersPool->getSiblingsGroupedByType($composableSkus);
        foreach ($siblingsGroupedByType as $productType => $siblingSkus) {
            $compositeSkusByTypes[$productType] = isset($compositeSkusByTypes[$productType])
                ? array_keys(array_flip($compositeSkusByTypes[$productType]) + array_flip($siblingSkus))
                : $siblingSkus;
        }

        $indexAlias = IndexAlias::from($indexName->getAlias()->getValue());
        foreach ($compositeSkusByTypes as $productType => $skus) {
            $data = $this->dataProvider->getSiblingsData($stockId, $productType, $skus, $indexAlias);
            if ($skuList) {
                // Partial reindex. When full reindex, replica table is used, so no cleaning is required.
                $this->indexStructureHandler->cleanIndex($indexName, new ArrayIterator($skus), $connectionName);
            }
            $this->indexStructureHandler->saveIndex($indexName, new ArrayIterator($data), $connectionName);
        }

        $this->reservationsIndexTable->dropTable($stockId);
    }
}
