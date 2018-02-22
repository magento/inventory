<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

use Magento\Catalog\Model\ProductRepository;
use Magento\CatalogInventory\Model\StockRegistryStorage;

/** @var \Magento\Framework\Registry $registry */
$registry = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->get(\Magento\Framework\Registry::class);

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

$productSku = 'simple';
$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
/** @var ProductRepository $repository */
$repository = $objectManager->get(ProductRepository::class);
/** @var StockRegistryStorage $stockRegistryStorage */
$stockRegistryStorage = $objectManager->get(StockRegistryStorage::class);

try {
    $product = $repository->get($productSku);
    // remove stock item from cache
    $stockRegistryStorage->removeStockItem($product->getId());
    $product->delete();
} catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
    //Entity already deleted
}

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
