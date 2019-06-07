<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Catalog\Setup\CategorySetup;
use Magento\Eav\Model\Config;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Eav\Api\AttributeRepositoryInterface;

$eavConfig = Bootstrap::getObjectManager()->get(Config::class);
$attribute = $eavConfig->getAttribute('catalog_product', 'test_configurable');

$eavConfig->clear();

$installer = Bootstrap::getObjectManager()->create(CategorySetup::class);

if (!$attribute->getId()) {

    /** @var $attribute Attribute */
    $attribute = Bootstrap::getObjectManager()->create(
        Attribute::class
    );

    /** @var AttributeRepositoryInterface $attributeRepository */
    $attributeRepository = Bootstrap::getObjectManager()->create(AttributeRepositoryInterface::class);

    $attribute->setData(
        [
            'attribute_code' => 'test_configurable',
            'entity_type_id' => $installer->getEntityTypeId('catalog_product'),
            'is_global' => 1,
            'is_user_defined' => 1,
            'frontend_input' => 'select',
            'is_unique' => 0,
            'is_required' => 0,
            'is_searchable' => 0,
            'is_visible_in_advanced_search' => 0,
            'is_comparable' => 0,
            'is_filterable' => 0,
            'is_filterable_in_search' => 0,
            'is_used_for_promo_rules' => 0,
            'is_html_allowed_on_front' => 1,
            'is_visible_on_front' => 0,
            'used_in_product_listing' => 0,
            'used_for_sort_by' => 0,
            'frontend_label' => ['Test Configurable'],
            'backend_type' => 'int',
            'option' => [
                'value' => ['option_0' => ['Option 1'], 'option_1' => ['Option 2']],
                'order' => ['option_0' => 1, 'option_1' => 2],
            ],
        ]
    );

    $attributeRepository->save($attribute);

    /* Assign attribute to attribute set */
    $installer->addAttributeToGroup('catalog_product', 'Default', 'General', $attribute->getId());
}

$eavConfig->clear();
