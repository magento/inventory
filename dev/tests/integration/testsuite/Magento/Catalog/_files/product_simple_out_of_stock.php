<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

\Magento\TestFramework\Helper\Bootstrap::getInstance()->reinitialize();

/** @var \Magento\TestFramework\ObjectManager $objectManager */
$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

/** @var $product \Magento\Catalog\Model\Product */
$product = $objectManager->create(\Magento\Catalog\Model\Product::class);
$product->isObjectNew(true);
$product->setTypeId(\Magento\Catalog\Model\Product\Type::TYPE_SIMPLE)
    ->setId(1)
    ->setAttributeSetId(4)
    ->setWebsiteIds([1])
    ->setName('Simple Product Out of Stock')
    ->setSku('simple-out-of-stock')
    ->setPrice(10)
    ->setWeight(1)
    ->setShortDescription("Short description Out of Stock")
    ->setTaxClassId(0)
    ->setDescription('Description with <b>html tag</b> for out of stock')
    ->setMetaTitle('meta title Out of Stock')
    ->setMetaKeyword('meta keyword Out of Stock')
    ->setMetaDescription('meta description Out of Stock')
    ->setVisibility(\Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH)
    ->setStatus(\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED)
    ->setStockData(
        [
            'use_config_manage_stock'   => 1,
            'qty'                       => 0,
            'is_qty_decimal'            => 0,
            'is_in_stock'               => 0,
        ]
    )->setCanSaveCustomOptions(true)
    ->setHasOptions(true);

/** @var \Magento\Catalog\Api\ProductRepositoryInterface $productRepository */
$productRepository = $objectManager->create(\Magento\Catalog\Api\ProductRepositoryInterface::class);
$productRepository->save($product);
