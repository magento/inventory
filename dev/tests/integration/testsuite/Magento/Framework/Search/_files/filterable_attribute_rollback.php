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
/** @var $attribute \Magento\Catalog\Model\ResourceModel\Eav\Attribute */
$attribute = $objectManager->create(\Magento\Catalog\Model\ResourceModel\Eav\Attribute::class);
$attribute->loadByCode(\Magento\Catalog\Model\Product::ENTITY, 'select_attribute');

/** @var $selectOptions \Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\Collection */
$selectOptions = $objectManager->create(
    \Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\Collection::class
);
$registry = $objectManager->get(\Magento\Framework\Registry::class);

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

$selectOptions->setAttributeFilter($attribute->getId());
/* Delete simple products per each select(dropdown) option */
foreach ($selectOptions as $option) {
    /** @var $product \Magento\Catalog\Model\Product */
    $product = $objectManager->create(\Magento\Catalog\Model\Product::class);
    $productRepository = $objectManager->get(Magento\Catalog\Model\ProductRepository::class);
    $product = $product->loadByAttribute('sku', 'simple_product_' . $option->getId());
    if ($product->getId()) {
        $productRepository->delete($product);
    }
}
if ($attribute->getId()) {
    $attribute->delete();
}

$attribute->loadByCode($installer->getEntityTypeId('catalog_product'), 'multiselect_attribute');
if ($attribute->getId()) {
    $attribute->delete();
}

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
