<?php
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Model;

/**
 * Stock Index table name resolver. Get stock index table by stock id.
 *
 * @api
 */
interface StockIndexTableNameResolverInterface
{
    /**
     * Resolves the stock index table name by stock ID, returning the corresponding table name as a string.
     *
     * @param int $stockId
     * @return string
     */
    public function execute(int $stockId): string;
}
