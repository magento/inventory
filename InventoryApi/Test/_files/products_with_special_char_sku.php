<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Catalog\Api\Data\ProductInterfaceFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Type;
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();
/** @var ProductInterfaceFactory $productFactory */
$productFactory = $objectManager->get(ProductInterfaceFactory::class);
/** @var ProductRepositoryInterface $productRepository */
$productRepository = $objectManager->get(ProductRepositoryInterface::class);
$productRepository->cleanCache();

$stockData = [
    'Test-Sku-&`!@$#%^*+="' => [
        'qty' => 10,
        'is_in_stock' => true,
        'manage_stock' => true,
        'is_qty_decimal' => false
    ]
];
$product = $productFactory->create();
$product->setTypeId(Type::TYPE_SIMPLE)
    ->setAttributeSetId(4)
    ->setName('Simple Product Test')
    ->setSku('Test-Sku-&`!@$#%^*+="')
    ->setPrice(10)
    ->setStockData($stockData['Test-Sku-&`!@$#%^*+="'])
    ->setStatus(Status::STATUS_ENABLED);
$productRepository->save($product);
