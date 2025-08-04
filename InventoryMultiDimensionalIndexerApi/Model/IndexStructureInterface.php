<?php
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryMultiDimensionalIndexerApi\Model;

/**
 * Represent manipulation with index structure
 *
 * @api
 */
interface IndexStructureInterface
{
    /**
     * Create the Index Structure
     *
     * @param IndexName $indexName
     * @param string $connectionName
     * @throws \Magento\Framework\Exception\StateException
     * @return void
     */
    public function create(IndexName $indexName, string $connectionName): void;

    /**
     * Delete the given Index
     *
     * @param IndexName $indexName
     * @param string $connectionName
     * @return void
     */
    public function delete(IndexName $indexName, string $connectionName): void;

    /**
     * Checks whether the Index exits
     *
     * @param IndexName $indexName
     * @param string $connectionName
     * @return bool
     */
    public function isExist(IndexName $indexName, string $connectionName): bool;
}
