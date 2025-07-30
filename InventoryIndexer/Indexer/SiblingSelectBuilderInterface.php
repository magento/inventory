<?php
/**
 * Copyright 2025 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Indexer;

use Magento\Framework\DB\Select;
use Magento\InventoryMultiDimensionalIndexerApi\Model\IndexName;

interface SiblingSelectBuilderInterface
{
    /**
     * Prepare select for sibling products.
     *
     * @param IndexName $indexName
     * @param array $skuList
     * @return Select
     */
    public function getSelect(IndexName $indexName, array $skuList = []): Select;
}
