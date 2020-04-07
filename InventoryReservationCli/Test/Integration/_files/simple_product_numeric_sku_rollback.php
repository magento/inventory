<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\TestFramework\Helper\Bootstrap;

Bootstrap::getInstance()->getInstance()->reinitialize();
$registry = Bootstrap::getObjectManager()->get(Registry::class);
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);
$productRepository = Bootstrap::getObjectManager()->get(ProductRepositoryInterface::class);
try {
    $product = $productRepository->get('12345', false, null, true);
    $productRepository->delete($product);
} catch (NoSuchEntityException $e) {
    //Product already deleted.
}
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
