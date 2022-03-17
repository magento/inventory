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
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Module\Manager;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\SourceItemRepositoryInterface;
use Magento\InventoryApi\Api\SourceItemsDeleteInterface;
use Magento\InventoryCatalogApi\Api\DefaultSourceProviderInterface;
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();
/** @var ProductInterfaceFactory $productFactory */
$productFactory = $objectManager->get(ProductInterfaceFactory::class);
/** @var ProductRepositoryInterface $productRepository */
$productRepository = $objectManager->get(ProductRepositoryInterface::class);
$productRepository->cleanCache();

$firstProduct = $productFactory->create();
$firstProduct->setTypeId(Type::TYPE_SIMPLE)
    ->setAttributeSetId(4)
    ->setName('Simple Product1')
    ->setSku('01234')
    ->setPrice(10)
    ->setStockData([
        'qty' => 100,
        'is_in_stock' => true,
        'manage_stock' => true,
        'is_qty_decimal' => true,
    ])
    ->setStatus(Status::STATUS_ENABLED);
$productRepository->save($firstProduct);
$secondProduct = $productFactory->create();
$secondProduct->setTypeId(Type::TYPE_SIMPLE)
    ->setAttributeSetId(4)
    ->setName('Simple Product2')
    ->setSku('1234')
    ->setPrice(10)
    ->setStockData([
        'qty' => 100,
        'is_in_stock' => true,
        'manage_stock' => true,
        'is_qty_decimal' => true,
    ])
    ->setStatus(Status::STATUS_ENABLED);
$productRepository->save($secondProduct);

/** @var Manager $moduleManager */
$moduleManager = $objectManager->get(Manager::class);
if ($moduleManager->isEnabled('Magento_InventoryCatalog')) {
    /** @var SearchCriteriaBuilder $searchCriteriaBuilder */
    $searchCriteriaBuilder = $objectManager->get(SearchCriteriaBuilder::class);
    /** @var DefaultSourceProviderInterface $defaultSourceProvider */
    $defaultSourceProvider = $objectManager->get(DefaultSourceProviderInterface::class);
    /** @var SourceItemRepositoryInterface $sourceItemRepository */
    $sourceItemRepository = $objectManager->get(SourceItemRepositoryInterface::class);
    /** @var SourceItemsDeleteInterface $sourceItemsDelete */
    $sourceItemsDelete = $objectManager->get(SourceItemsDeleteInterface::class);

    // Un Assign created product from default Source
    $searchCriteria = $searchCriteriaBuilder
        ->addFilter(SourceItemInterface::SKU, ['01234', '1234'], 'in')
        ->addFilter(SourceItemInterface::SOURCE_CODE, $defaultSourceProvider->getCode())
        ->create();
    $sourceItems = $sourceItemRepository->getList($searchCriteria)->getItems();
    if (count($sourceItems)) {
        $sourceItemsDelete->execute($sourceItems);
    }
}
