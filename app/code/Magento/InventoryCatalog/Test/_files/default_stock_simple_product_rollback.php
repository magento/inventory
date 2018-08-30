<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\CatalogInventory\Model\Stock\Status;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();
$registry = $objectManager->get(Registry::class);
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);
$productRepository = $objectManager->get(ProductRepositoryInterface::class);
$skus = ['simple_in_stock', 'simple_out_of_stock'];
foreach ($skus as $sku) {
    try {
        $product = $productRepository->get($sku, true);
        $stockStatus = $objectManager->create(Status::class);
        $stockStatus->load($product->getEntityId(), 'product_id');
        $stockStatus->delete();
        $productRepository->delete($product);
    } catch (NoSuchEntityException $e) {
        //Product already removed
    }
}
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
