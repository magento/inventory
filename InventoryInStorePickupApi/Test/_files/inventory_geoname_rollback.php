<?php
/**
 * Copyright 2019 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

use Magento\Framework\App\ResourceConnection;
use Magento\TestFramework\Helper\Bootstrap;

/** @var ResourceConnection $resource */
$resource = Bootstrap::getObjectManager()->get(ResourceConnection::class);

$connection = $resource->getConnection();
$tableName = $resource->getTableName('inventory_geoname');

$connection->delete($tableName, 'country_code in ("DE", "IT", "FR", "US")');
