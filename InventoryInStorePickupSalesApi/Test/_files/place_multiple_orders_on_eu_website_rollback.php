<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Registry;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;

/** @var Registry $registry */
$registry = Bootstrap::getObjectManager()->get(Registry::class);

/** @var OrderRepositoryInterface $orderRepository */
$orderRepository = Bootstrap::getObjectManager()->get(OrderRepositoryInterface::class);
/** @var OrderManagementInterface $orderManagement */
$orderManagement = Bootstrap::getObjectManager()->get(OrderManagementInterface::class);
/** @var SearchCriteriaBuilder $searchCriteriaBuilder */
$searchCriteriaBuilder = Bootstrap::getObjectManager()->get(SearchCriteriaBuilder::class);

$ids = [
    'in_store_pickup_test_order-SKU-1',
    'in_store_pickup_test_order-SKU-2',
    'in_store_pickup_test_order-SKU-3',
    'in_store_pickup_test_order-SKU-4',
    'in_store_pickup_test_order-SKU-6'
];
$searchCriteria = $searchCriteriaBuilder
    ->addFilter('increment_id', $ids, 'in')
    ->create();

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

/** @var OrderInterface $order */
foreach ($orderRepository->getList($searchCriteria)->getItems() as $order) {
    $orderManagement->cancel($order->getEntityId());
    $orderRepository->delete($order);
}
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
