<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\TestFramework\Workaround\Override\Fixture\Resolver;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\CatalogInventory\Api\StockItemRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;

Resolver::getInstance()->requireDataFixture('Magento/Sales/_files/order_with_two_configurable_variations.php');

$objectManager = Bootstrap::getObjectManager();

/** @var ProductRepositoryInterface $productRepository */
$productRepository = $objectManager->get(ProductRepositoryInterface::class);
$stockItemRepository = $objectManager->get(StockItemRepositoryInterface::class);

$product = $productRepository->get('configurable');
$stockItem = $product->getExtensionAttributes()->getStockItem();
$stockItem->setUseConfigManageStock(false);
$stockItem->setManageStock(false);
$stockItemRepository->save($stockItem);
