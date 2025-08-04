<?php
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryMultiDimensionalIndexerApi\Model;

/**
 * Resolve index name by IndexName object
 *
 * @api
 */
interface IndexNameResolverInterface
{
    /**
     * Resolve index name by IndexName object
     *
     * @param IndexName $indexName
     * @return string
     */
    public function resolveName(IndexName $indexName): string;
}
