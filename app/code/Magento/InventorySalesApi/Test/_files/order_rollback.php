<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();
/** @var OrderRepositoryInterface $productRepository */
$orderRepository = $objectManager->get(OrderRepositoryInterface::class);

$order = $orderRepository->get(1);
$orderRepository->delete($order);
