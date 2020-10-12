<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\CatalogInventory\Api\StockStatusCriteriaInterfaceFactory;
use Magento\CatalogInventory\Api\StockStatusRepositoryInterface;
use Magento\Framework\Registry;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Catalog\Api\Data\ProductInterface;

$objectManager = Bootstrap::getObjectManager();
/** @var ProductRepositoryInterface $productRepository */
$productRepository = $objectManager->create(ProductRepositoryInterface::class);
/** @var Registry $registry */
$registry = $objectManager->get(Registry::class);
/** @var StockStatusRepositoryInterface $stockStatusRepository */
$stockStatusRepository = $objectManager->create(StockStatusRepositoryInterface::class);
/** @var StockStatusCriteriaInterfaceFactory $stockStatusCriteriaFactory */
$stockStatusCriteriaFactory = $objectManager->create(StockStatusCriteriaInterfaceFactory::class);

$currentArea = $registry->registry('isSecureArea');
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

$skus = ['123', '123ABC', '456', '456DEF'];
foreach ($skus as $sku) {
    try {
        /** @var ProductInterface $product */
        $product = $productRepository->get($sku);
        $productRepository->delete($product);
    } catch (\Magento\Framework\Exception\NoSuchEntityException $exception) {
        // product doesn't exist;
        continue;
    }

    $criteria = $stockStatusCriteriaFactory->create();
    $criteria->setProductsFilter($product->getId());

    $result = $stockStatusRepository->getList($criteria);
    if ($result->getTotalCount()) {
        $stockStatus = current($result->getItems());
        $stockStatusRepository->delete($stockStatus);
    }
}

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', $currentArea);
