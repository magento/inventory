<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Framework\App\ResourceConnection;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryApi\Api\Data\StockInterface;

/** @var ResourceConnection $connection */
$connection = Bootstrap::getObjectManager()->get(ResourceConnection::class);
$connection->getConnection()->delete(
    $connection->getTableName('inventory_source'),
    [
        SourceInterface::SOURCE_ID . ' NOT IN (?)' => [1, 10, 20, 30, 40, 50],
    ]
);
$connection->getConnection()->delete(
    $connection->getTableName('inventory_stock'),
    [
        StockInterface::STOCK_ID . ' NOT IN (?)' => [1, 10, 20, 30],
    ]
);
