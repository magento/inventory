<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Catalog\Api\Data\ProductLinkInterfaceFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Framework\Api\DataObjectHelper;
use Magento\GroupedProduct\Model\Product\Type\Grouped;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterfaceFactory;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;
use Magento\TestFramework\Helper\Bootstrap;

\Magento\TestFramework\Helper\Bootstrap::getInstance()->reinitialize();
$objectManager = Bootstrap::getObjectManager();

/** @var ProductRepositoryInterface $productRepository */
$productRepository = $objectManager->get(ProductRepositoryInterface::class);
$productLinkFactory = $objectManager->get(ProductLinkInterfaceFactory::class);
$productIds = [11, 22, 33];

foreach ($productIds as $productId) {
    /** @var $product Product */
    $product = $objectManager->create(Product::class);
    $product->setTypeId(Type::TYPE_SIMPLE)
        ->setId($productId)
        ->setWebsiteIds([1])
        ->setAttributeSetId(4)
        ->setName('Simple ' . $productId)
        ->setSku('simple_' . $productId)
        ->setPrice(100)
        ->setVisibility(Visibility::VISIBILITY_BOTH)
        ->setStatus(Status::STATUS_ENABLED);

    $productRepository->save($product);
}

$gropedProductsData = [1 => ['simple_11', 'simple_33'], 2 => ['simple_22', 'simple_33']];
foreach ($gropedProductsData as $id => $childSkus) {
    /** @var $groupedProduct Product */
    $groupedProduct = $objectManager->create(Product::class);
    $groupedProduct->setTypeId(Grouped::TYPE_CODE)
        ->setId($id)
        ->setWebsiteIds([1])
        ->setAttributeSetId(4)
        ->setName('Grouped Product ' . $id)
        ->setSku('grouped_' . $id)
        ->setVisibility(Visibility::VISIBILITY_BOTH)
        ->setStatus(Status::STATUS_ENABLED);

    $links = [];
    foreach ($childSkus as $sku) {
        /** @var \Magento\Catalog\Api\Data\ProductLinkInterface $productLink */
        $productLink = $productLinkFactory->create();
        $productLink->setSku($groupedProduct->getSku())
            ->setLinkType('associated')
            ->setLinkedProductSku($sku)
            ->setLinkedProductType(Type::TYPE_SIMPLE)
            ->getExtensionAttributes()
            ->setQty(1);
        $links[] = $productLink;
    }

    $groupedProduct->setProductLinks($links);
    $productRepository->save($groupedProduct);
}

$sourcesItemsData = [
    [
        SourceItemInterface::SOURCE_CODE => 'source-code-1',
        SourceItemInterface::SKU => 'simple_11',
        SourceItemInterface::QUANTITY => 100,
        SourceItemInterface::STATUS => SourceItemInterface::STATUS_IN_STOCK,
    ],
    [
        SourceItemInterface::SOURCE_CODE => 'source-code-1',
        SourceItemInterface::SKU => 'simple_22',
        SourceItemInterface::QUANTITY => 100,
        SourceItemInterface::STATUS => SourceItemInterface::STATUS_IN_STOCK,
    ],
    [
        SourceItemInterface::SOURCE_CODE => 'source-code-1',
        SourceItemInterface::SKU => 'simple_33',
        SourceItemInterface::QUANTITY => 0,
        SourceItemInterface::STATUS => SourceItemInterface::STATUS_OUT_OF_STOCK,
    ]
];

/** @var DataObjectHelper $dataObjectHelper */
$dataObjectHelper = $objectManager->get(DataObjectHelper::class);
/** @var SourceItemInterfaceFactory $sourceItemFactory */
$sourceItemFactory = $objectManager->get(SourceItemInterfaceFactory::class);
/** @var  SourceItemsSaveInterface $sourceItemsSave */
$sourceItemsSave = $objectManager->get(SourceItemsSaveInterface::class);

$sourceItems = [];
foreach ($sourcesItemsData as $sourceItemData) {
    /** @var SourceItemInterface $source */
    $sourceItem = $sourceItemFactory->create();
    $dataObjectHelper->populateWithArray($sourceItem, $sourceItemData, SourceItemInterface::class);
    $sourceItems[] = $sourceItem;
}
$sourceItemsSave->execute($sourceItems);
