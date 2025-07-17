<?php
/**
 * Copyright 2025 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Indexer\Stock;

use ArrayIterator;
use Magento\Framework\App\ResourceConnection;
use Magento\InventoryIndexer\Indexer\SelectBuilder;
use Magento\InventoryIndexer\Indexer\SiblingProductsProviderInterface;
use Magento\InventoryMultiDimensionalIndexerApi\Model\IndexHandlerInterface;
use Magento\InventoryMultiDimensionalIndexerApi\Model\IndexName;

class IndexDataFiller
{
    /**
     * @param ResourceConnection $resourceConnection
     * @param SelectBuilder $selectBuilder
     * @param IndexHandlerInterface $indexStructureHandler
     * @param SiblingProductsProviderInterface[] $siblingProductsProviders
     */
    public function __construct(
        private readonly ResourceConnection $resourceConnection,
        private readonly SelectBuilder $selectBuilder,
        private readonly IndexHandlerInterface $indexStructureHandler,
        private readonly array $siblingProductsProviders = [],
    ) {
        (fn (SiblingProductsProviderInterface ...$siblingProductsProviders) => $siblingProductsProviders)(
            ...$this->siblingProductsProviders
        );
    }

    /**
     * Fill index with data.
     *
     * @param IndexName $indexName
     * @param int $stockId
     * @param array $skuList
     * @return void
     */
    public function fillIndex(IndexName $indexName, int $stockId, array $skuList = []): void
    {
        $select = $this->selectBuilder->getSelect($stockId, $skuList);
        $data = $this->resourceConnection->getConnection()->fetchAll($select);
        if ($skuList) {
            $this->indexStructureHandler->cleanIndex(
                $indexName,
                new ArrayIterator($skuList),
                ResourceConnection::DEFAULT_CONNECTION
            );
        }
        $this->indexStructureHandler->saveIndex(
            $indexName,
            new ArrayIterator($data),
            ResourceConnection::DEFAULT_CONNECTION
        );

        foreach ($this->siblingProductsProviders as $siblingProductsProvider) {
            if (!empty($skuList)) {
                // partial reindex
                $siblingSkus = $siblingProductsProvider->getSkus($skuList);
                if (!$siblingSkus) {
                    continue;
                }
            } else {
                // full reindex
                $siblingSkus = [];
            }

            $data = $siblingProductsProvider->getData($indexName, $siblingSkus);
            if ($siblingSkus) {
                $this->indexStructureHandler->cleanIndex(
                    $indexName,
                    new ArrayIterator($siblingSkus),
                    ResourceConnection::DEFAULT_CONNECTION
                );
            }
            $this->indexStructureHandler->saveIndex(
                $indexName,
                new ArrayIterator($data),
                ResourceConnection::DEFAULT_CONNECTION
            );
        }
    }
}
