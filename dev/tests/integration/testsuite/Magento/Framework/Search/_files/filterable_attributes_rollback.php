<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Catalog\Setup\CategorySetup;
use Magento\Framework\Registry;
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();
$registry = $objectManager->get(Registry::class);
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);
/** @var $productCollection \Magento\Catalog\Model\ResourceModel\Product\Collection */
$productCollection = $objectManager->create(Product::class)->getCollection();

$productRepository = $objectManager->get(Magento\Catalog\Model\ProductRepository::class);
foreach ($productCollection as $product) {
    $productRepository->delete($product);
}
/** @var $attribute Attribute */
$attribute = Bootstrap::getObjectManager()->create(
    Attribute::class
);
/** @var $installer CategorySetup */
$installer = $objectManager->create(CategorySetup::class);
foreach (range(1, 2) as $index) {
    $attribute->loadByCode($installer->getEntityTypeId('catalog_product'), 'select_attribute_' . $index);
    if ($attribute->getId()) {
        $attribute->delete();
    }
}
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
