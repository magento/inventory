<?php
/**
 * Copyright 2021 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();
$resourceConnection = $objectManager->create(Magento\Framework\App\ResourceConnection::class);
$connection = $resourceConnection->getConnection();
$tableName = $resourceConnection->getTableName('inventory_reservation');
$qry = $connection->delete($tableName);
