<?php
/**
 * Copyright 2025 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Indexer\Stock;

use Magento\Framework\App\ResourceConnection;
use Magento\InventoryIndexer\Indexer\SelectBuilder;
use Magento\InventoryIndexer\Indexer\SiblingSelectBuilderInterface;
use Magento\InventoryMultiDimensionalIndexerApi\Model\IndexAlias;

class DataProvider
{
    /**
     * @param ResourceConnection $resourceConnection
     * @param SelectBuilder $selectBuilder
     * @param SiblingSelectBuilderInterface[] $siblingSelectBuilders
     */
    public function __construct(
        private readonly ResourceConnection $resourceConnection,
        private readonly SelectBuilder $selectBuilder,
        private readonly array $siblingSelectBuilders = [],
    ) {
    }

    /**
     * Get data using default select builder.
     *
     * @param int $stockId
     * @param string[] $skuList
     * @return array<string, array{sku: string, quantity: float, is_salable: int}>
     */
    public function getData(int $stockId, array $skuList = []): array
    {
        $select = $this->selectBuilder->getSelect($stockId, $skuList);
        $data = $this->resourceConnection->getConnection()->fetchAll($select);
        $data = array_column($data, null, 'sku');

        return $data;
    }

    /**
     * Get data for sibling products of specified type.
     *
     * @param int $stockId
     * @param string $productType
     * @param string[] $skuList
     * @param IndexAlias $indexAlias
     * @return array<string, array{sku: string, quantity: float, is_salable: int}>
     */
    public function getSiblingsData(
        int $stockId,
        string $productType,
        array $skuList = [],
        IndexAlias $indexAlias = IndexAlias::MAIN
    ): array {
        $siblingSelectBuilder = $this->siblingSelectBuilders[$productType];
        $select = $siblingSelectBuilder->getSelect($stockId, $skuList, $indexAlias);
        $data = $this->resourceConnection->getConnection()->fetchAll($select);
        $data = array_column($data, null, 'sku');

        return $data;
    }
}
