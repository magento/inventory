<?php
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryMultiDimensionalIndexerApi\Model;

/**
 * Logic for switching active and replica index tables by IndexName object
 *
 * @api
 */
interface IndexTableSwitcherInterface
{
    /**
     * Switch active and replica index tables by IndexName object
     *
     * @param IndexName $indexName
     * @param string $connectionName
     * @return void
     */
    public function switch(IndexName $indexName, string $connectionName): void;
}
