<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\CatalogInventory\Api\StockStatusCriteriaInterfaceFactory;
use Magento\CatalogInventory\Api\StockStatusRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Catalog\Api\Data\ProductInterface;

$objectManager = Bootstrap::getObjectManager();
/** @var ProductRepositoryInterface $productRepository */
$productRepository = $objectManager->create(ProductRepositoryInterface::class);
/** @var StockStatusRepositoryInterface $stockStatusRepository */
$stockStatusRepository = $objectManager->create(StockStatusRepositoryInterface::class);
/** @var StockStatusCriteriaInterfaceFactory $stockStatusCriteriaFactory */
$stockStatusCriteriaFactory = $objectManager->create(StockStatusCriteriaInterfaceFactory::class);

try {
    /** @var ProductInterface $product */
    $product = $productRepository->get('Test-Sku-&`!@$#%^*+="');
    $productRepository->delete($product);
} catch (NoSuchEntityException $exception) {

}

$criteria = $stockStatusCriteriaFactory->create();
$criteria->setProductsFilter($product->getId());

$result = $stockStatusRepository->getList($criteria);
if ($result->getTotalCount()) {
    $stockStatus = current($result->getItems());
    $stockStatusRepository->delete($stockStatus);
}
