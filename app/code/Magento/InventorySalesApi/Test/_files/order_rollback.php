<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Framework\Registry;

$objectManager = Bootstrap::getObjectManager();
/** @var OrderRepositoryInterface $productRepository */
$orderRepository = $objectManager->get(OrderRepositoryInterface::class);
/** @var Registry $registry */
$registry = Bootstrap::getObjectManager()->get(Registry::class);

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

$order = $orderRepository->get(1);
$orderRepository->delete($order);

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
