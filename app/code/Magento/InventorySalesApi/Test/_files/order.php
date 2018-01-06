<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderInterfaceFactory;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Api\Data\OrderItemInterfaceFactory;
use Magento\Sales\Api\Data\OrderPaymentInterfaceFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();
/** @var OrderInterfaceFactory $productFactory */
$orderFactory = $objectManager->get(OrderInterfaceFactory::class);
/** @var OrderRepositoryInterface $productRepository */
$orderRepository = $objectManager->get(OrderRepositoryInterface::class);
/** @var OrderItemInterfaceFactory $productFactory */
$orderItemFactory = $objectManager->get(OrderItemInterfaceFactory::class);
/** @var OrderPaymentInterfaceFactory $productFactory */
$orderItemFactory = $objectManager->get(OrderPaymentInterfaceFactory::class);

$orderItemsData = [
    [
        OrderItemInterface::SKU => 'SKU-1',
        OrderItemInterface::ITEM_ID => 1,
        OrderItemInterface::ORDER_ID => 1,
        OrderItemInterface::QTY_ORDERED => 3.5,
    ],
    [
        OrderItemInterface::SKU => 'SKU-2',
        OrderItemInterface::ITEM_ID => 2,
        OrderItemInterface::ORDER_ID => 1,
        OrderItemInterface::QTY_ORDERED => 4,
    ],
];
$orderItems = [];
foreach ($orderItemsData as $orderItemsDatum) {
    $orderItems[] = $orderItemFactory->create(['data' => $orderItemsDatum]);
}

$orderData = [
    [
        OrderInterface::ENTITY_ID => 1,
        OrderInterface::STATE => 'pending',
        OrderInterface::STATUS => 'pending',
        OrderInterface::ITEMS => $orderItems,
        OrderInterface::PAYMENT => ''
    ]
];
$order = $orderFactory->create(['date' => $orderData]);
$orderRepository->save($order);
