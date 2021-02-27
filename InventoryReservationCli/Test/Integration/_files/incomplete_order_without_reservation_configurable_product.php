<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Sales\Api\OrderItemRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

Resolver::getInstance()->requireDataFixture('Magento/Sales/_files/order_configurable_product.php');

$objectManager = Bootstrap::getObjectManager();

/** @var ProductRepositoryInterface $productRepository */
$productRepository = $objectManager->create(ProductRepositoryInterface::class);
$configurableProduct = $productRepository->get('configurable');
$simpleProductId = current($configurableProduct->getExtensionAttributes()->getConfigurableProductLinks());
/** @var ProductInterface $simpleProduct */
$simpleProduct = $productRepository->getById($simpleProductId);
$order = $objectManager->create(Order::class)->loadByIncrementId('100000001');
/** @var OrderItemRepositoryInterface $orderItemsRepository */
$orderItemsRepository = $objectManager->create(OrderItemRepositoryInterface::class);

foreach ($order->getItems() as $orderItem) {
    if ($orderItem->getTypeId() !== $configurableProduct->getTypeId()) {
        $orderItem->setSku($simpleProduct->getSku());
        $orderItemsRepository->save($orderItem);
    }
}
