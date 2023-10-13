<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\TestFramework\Helper\Bootstrap;
use Magento\Sales\Model\Order;
use Magento\InventorySalesApi\Api\Data\SalesEventInterface;

/** @var \Magento\Framework\ObjectManagerInterface $objectManager */
$objectManager = Bootstrap::getObjectManager();

/** @var Magento\Framework\App\ResourceConnection $resourceConnection */
$resourceConnection = $objectManager->create(Magento\Framework\App\ResourceConnection::class);

/** @var \Magento\Sales\Model\Order $order */
$order = $objectManager->create(Order::class)->loadByIncrementId('100000001');

$connection = $resourceConnection->getConnection();
$tableName = $resourceConnection->getTableName('inventory_reservation');

$payload = [
    'stock_id' => 1,
    'sku' => 'simple',
    'quantity' => -2,
    'metadata' => '{"event_type":"' . SalesEventInterface::EVENT_ORDER_PLACED
        . '","object_type":"order","object_id":"' . (string)$order->getEntityId() . '","objectIncrementId":""}'
];

$qry = $connection->insert($tableName, $payload);
