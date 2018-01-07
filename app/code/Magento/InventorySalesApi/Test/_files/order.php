<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderInterfaceFactory;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Api\Data\OrderItemInterfaceFactory;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Api\Data\OrderPaymentInterfaceFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();
/** @var OrderInterfaceFactory $orderFactory */
$orderFactory = $objectManager->get(OrderInterfaceFactory::class);
/** @var OrderRepositoryInterface $orderRepository */
$orderRepository = $objectManager->get(OrderRepositoryInterface::class);
/** @var OrderItemInterfaceFactory $orderItemFactory */
$orderItemFactory = $objectManager->get(OrderItemInterfaceFactory::class);
/** @var OrderPaymentInterfaceFactory $orderPaymentFactory */
$orderPaymentFactory = $objectManager->get(OrderPaymentInterfaceFactory::class);
/** @var StoreRepositoryInterface $storeRepository */
$storeRepository = $objectManager->get(StoreRepositoryInterface::class);
/** @var ProductRepositoryInterface $productRepository */
$productRepository = $objectManager->get(ProductRepositoryInterface::class);

$product = $productRepository->get('SKU-1');
$orderItems = [
    $orderItemFactory->create(
        [
            'data' => [
                OrderItemInterface::SKU => $product->getSku(),
                OrderItemInterface::PRODUCT_ID => $product->getId(),
                OrderItemInterface::ITEM_ID => 1,
                OrderItemInterface::ORDER_ID => 1,
                OrderItemInterface::QTY_ORDERED => 12,
            ]
        ]
    )
];
$payment = $orderPaymentFactory->create(
    [
        'data' => [
            OrderPaymentInterface::ENTITY_ID => 1,
            OrderPaymentInterface::METHOD => 'free'
        ]
    ]
);
/** @var \Magento\Store\Api\Data\StoreInterface $store */
$store = $storeRepository->get('default');
$orderData = [
    OrderInterface::ENTITY_ID => 1,
    OrderInterface::STATE => 'pending',
    OrderInterface::STATUS => 'pending',
    OrderInterface::ITEMS => $orderItems,
    OrderInterface::PAYMENT => $payment,
    OrderInterface::STORE_ID => $store->getId()
];
$order = $orderFactory->create(['data' => $orderData]);
$orderRepository->save($order);
