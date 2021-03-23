<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Framework\DB\Transaction;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\ShipmentFactory;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

Resolver::getInstance()
    ->requireDataFixture('Magento/Sales/_files/order.php');

$objectManager = Bootstrap::getObjectManager();

/** @var Order $order */
$order = $objectManager->create(Order::class)
    ->loadByIncrementId('100000001');

/** @var OrderManagementInterface $orderManagement */
$orderManagement = $objectManager->create(OrderManagementInterface::class);
$orderManagement->place($order);

/** @var Transaction $transaction */
$transaction = $objectManager->create(Transaction::class);

$items = [];
foreach ($order->getItems() as $orderItem) {
    $items[$orderItem->getId()] = $orderItem->getQtyOrdered()/2;
}
$shipment = $objectManager->get(ShipmentFactory::class)
    ->create($order, $items);
$shipment->register();

$transaction->addObject($shipment)
    ->addObject($order)
    ->save();
