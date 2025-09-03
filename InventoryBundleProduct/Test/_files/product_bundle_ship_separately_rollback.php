<?php
/**
 * Copyright 2020 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Registry;
use Magento\TestFramework\Helper\Bootstrap;

$registry = Bootstrap::getObjectManager()->get(Registry::class);
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);
$productRepository = Bootstrap::getObjectManager()->get(ProductRepositoryInterface::class);
try {
    $product = $productRepository->get('bundle-ship-separately');
    $productRepository->delete($product);
} catch (Exception $e) {
    //Product already deleted.
}
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
