<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/* Create attribute */
$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

/** @var $installer \Magento\Catalog\Setup\CategorySetup */
$installer = $objectManager->create(
    \Magento\Catalog\Setup\CategorySetup::class,
    ['resourceName' => 'catalog_setup']
);
$registry = $objectManager->get(\Magento\Framework\Registry::class);

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

/** @var $product \Magento\Catalog\Model\Product */
$product = $objectManager->create(\Magento\Catalog\Model\Product::class);
$productRepository = $objectManager->get(Magento\Catalog\Model\ProductRepository::class);
$product = $product->loadByAttribute('sku', 'simple_product_with_date_attribute');
if ($product->getId()) {
    $productRepository->delete($product);
}

/** @var $attribute \Magento\Catalog\Model\ResourceModel\Eav\Attribute */
$attribute = $objectManager->create(\Magento\Catalog\Model\ResourceModel\Eav\Attribute::class);
$attribute->loadByCode($installer->getEntityTypeId('catalog_product'), 'date_attribute');
if ($attribute->getId()) {
    $attribute->delete();
}

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
