<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\SourceItemRepositoryInterface;
use Magento\InventoryApi\Api\SourceItemsDeleteInterface;
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();
/** @var \Magento\Framework\Registry $registry */
$registry = $objectManager->get(\Magento\Framework\Registry::class);

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

$skuList = ['simple_11', 'simple_22', 'simple_23', 'grouped_1', 'grouped_2'];

/** @var ProductRepositoryInterface $productRepository */
$productRepository = $objectManager->get(ProductRepositoryInterface::class);
foreach ($skuList as $sku) {
    try {
        $product = $productRepository->get($sku, false, null, true);
        $productRepository->delete($product);
    } catch (NoSuchEntityException $e) {
        //Product already removed
    }
}

/** @var SourceItemRepositoryInterface $sourceItemRepository */
$sourceItemRepository = $objectManager->get(SourceItemRepositoryInterface::class);
/** @var SourceItemsDeleteInterface $sourceItemsDelete */
$sourceItemsDelete = $objectManager->get(SourceItemsDeleteInterface::class);
/** @var SearchCriteriaBuilder $searchCriteriaBuilder */
$searchCriteriaBuilder = $objectManager->get(SearchCriteriaBuilder::class);
$searchCriteria = $searchCriteriaBuilder->addFilter(SourceItemInterface::SKU, $skuList, 'in')->create();
$sourceItems = $sourceItemRepository->getList($searchCriteria)->getItems();
if (!empty($sourceItems)) {
    $sourceItemsDelete->execute($sourceItems);
}

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
