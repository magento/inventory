<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Bundle\Api\Data\LinkInterfaceFactory;
use Magento\Bundle\Api\Data\OptionInterfaceFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\Product\Type\AbstractType;
use Magento\Catalog\Model\Product\Visibility;
use Magento\TestFramework\Helper\Bootstrap;

require __DIR__ . '/../../../InventoryApi/Test/_files/products.php';

$productRepository = Bootstrap::getObjectManager()->create(ProductRepositoryInterface::class);
$product = $productRepository->get('SKU-3');
$bundleProduct = Bootstrap::getObjectManager()->create(Product::class);
$bundleProduct->setTypeId(Type::TYPE_BUNDLE)
    ->setAttributeSetId(4)
    ->setName('Bundle Product Ship Together')
    ->setSku('bundle-ship-together')
    ->setVisibility(Visibility::VISIBILITY_BOTH)
    ->setStatus(Status::STATUS_ENABLED)
    ->setPriceView(1)
    ->setPriceType(1)
    ->setShipmentType(AbstractType::SHIPMENT_TOGETHER)
    ->setPrice(10.0);

$optionData = [
    'title' => 'Bundle Product Items',
    'default_title' => 'Bundle Product Items',
    'type' => 'select',
    'required' => 1,
    'delete' => '',
    'sku' => $bundleProduct->getSku()
];
$linkData = [
    'product_id' => $product->getId(),
    'sku' => $product->getSku(),
    'qty' => 1,
    'selection_qty' => 1,
    'selection_can_change_qty' => 1,
    'delete' => '',
];

$link = Bootstrap::getObjectManager()->create(LinkInterfaceFactory::class)->create(['data' => $linkData]);
$option = Bootstrap::getObjectManager()->create(OptionInterfaceFactory::class)->create(['data' => $optionData]);
$option->setProductLinks([$link]);

$extension = $bundleProduct->getExtensionAttributes();
$extension->setBundleProductOptions([$option]);
$bundleProduct->setExtensionAttributes($extension);

$productRepository->save($bundleProduct);
