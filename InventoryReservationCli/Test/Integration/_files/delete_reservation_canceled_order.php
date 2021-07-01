<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\TestFramework\Helper\Bootstrap;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Serialize\SerializerInterface;

$objectManager = Bootstrap::getObjectManager();
$serializer = $objectManager->create(SerializerInterface::class);;
$resourceConnection = $objectManager->create(ResourceConnection::class);
$connection = $resourceConnection->getConnection();
$tableName = $resourceConnection->getTableName('inventory_reservation');
$query = $connection->select()->from($tableName);
$reservationIdCanceled = null;
foreach ($connection->fetchAll($query) as $item) {
    $metadata = $serializer->unserialize($item['metadata']);
    if (isset($metadata['event_type']) && $metadata['event_type'] === 'order_canceled') {
        $reservationIdCanceled = $item['reservation_id'];
    }
}
if ($reservationIdCanceled) {
    $connection->delete(
        $tableName,
        ['reservation_id = ?' => $reservationIdCanceled]
    );
}
