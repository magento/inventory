<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;

$om = Bootstrap::getObjectManager();
/** @var OrderRepositoryInterface $orderRepository */
$orderRepository = $om->get(OrderRepositoryInterface::class);
/** @var SearchCriteriaBuilder $searchCriteriaBuilder */
$searchCriteriaBuilder = $om->get(SearchCriteriaBuilder::class);

/** @var OrderInterface $order */
$order = current(
    $orderRepository->getList(
        $searchCriteriaBuilder
            ->addFilter(OrderInterface::INCREMENT_ID, 'in_store_pickup_test_order')
            ->create()
    )->getItems()
);

$order->getExtensionAttributes()
      ->setPickupLocationCode('eu-1');

$orderRepository->save($order);
