<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Category;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\TestFramework\Helper\Bootstrap;

/** @var ProductRepositoryInterface $productRepository */
$productRepository = $objectManager->create(ProductRepositoryInterface::class);

try {
    $product = $productRepository->get('simple_10', false, null, true);
    $productRepository->delete($product);
} catch (NoSuchEntityException $exception) {
    //Product already removed
}

/** @var $category Category */
$category = Bootstrap::getObjectManager()->create(Category::class);
$category->load(1234);
if ($category->getId()) {
    $category->delete();
}
