<?php
/**
 * Copyright 2025 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Indexer;

use Magento\InventoryMultiDimensionalIndexerApi\Model\IndexName;

interface SiblingProductsProviderInterface
{
    /**
     * Retrieve sibling product SKUs for a given SKUs.
     *
     * @param string[] $skus
     * @return string[]
     */
    public function getSkus(array $skus): array;

    /**
     * Retrieve sibling product data based on their SKUs.
     *
     * @param IndexName $indexName
     * @param array $skuList
     * @return array
     */
    public function getData(IndexName $indexName, array $skuList = []): array;
}
