<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\TestFramework\Helper\Bootstrap;

const ADMIN_STORE_ID = 0;

$objectManager = Bootstrap::getObjectManager();
/** @var ProductRepositoryInterface $productRepository */
$productRepository = $objectManager->get(ProductRepositoryInterface::class);

for ($i = 1; $i <= 6; $i++) {
    $product = $productRepository->get('SKU-' . $i);
    $product->setStatus(Status::STATUS_DISABLED);
    $product->setStoreId(ADMIN_STORE_ID);
    $productRepository->save($product);
}
