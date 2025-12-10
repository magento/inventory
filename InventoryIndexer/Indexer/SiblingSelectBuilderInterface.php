<?php
/**
 * Copyright 2025 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Indexer;

use Magento\Framework\DB\Select;
use Magento\InventoryMultiDimensionalIndexerApi\Model\IndexAlias;

interface SiblingSelectBuilderInterface
{
    /**
     * Prepare select for sibling products.
     *
     * @param int $stockId
     * @param string[] $skuList
     * @param IndexAlias $indexAlias
     * @return Select
     */
    public function getSelect(int $stockId, array $skuList = [], IndexAlias $indexAlias = IndexAlias::MAIN): Select;
}
