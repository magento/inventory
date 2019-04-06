<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\TestFramework\Helper\Bootstrap;
use Magento\Framework\App\ResourceConnection;

/** @var ResourceConnection $resource */
$resource = Bootstrap::getObjectManager()->get(ResourceConnection::class);

/** @var \Magento\Framework\DB\Adapter\AdapterInterface $connection */
$connection = $resource->getConnection();
$tableName = $connection->getTableName('inventory_geoname');

if ($connection->isTableExists($tableName)) {
    $values = ['DE', 'IT', 'FR', 'US'];
    $connection->delete($tableName, ['country_code IN (?)' => $values]);
}
