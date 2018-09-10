<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

use Magento\TestFramework\Helper\Bootstrap;
use Magento\Catalog\Api\ProductRepositoryInterface;

include __DIR__ . '/../../Catalog/_files/product_simple.php';

$productRepository = Bootstrap::getObjectManager()->get(ProductRepositoryInterface::class);
$product = $productRepository->get('simple');
/** @var $product \Magento\Catalog\Model\Product */
$product->getExtensionAttributes()->getStockItem()->setMinSaleQty(3);
$product->getExtensionAttributes()->getStockItem()->setUseConfigMinSaleQty(0);
$product->save();
