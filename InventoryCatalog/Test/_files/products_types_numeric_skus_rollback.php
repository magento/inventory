<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

use Magento\Bundle\Model\Product\Type as Bundle;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Type as Simple;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Downloadable\Model\Product\Type as Downloadable;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\GroupedProduct\Model\Product\Type\Grouped;
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();

/** @var Registry $registry */
$registry = $objectManager->get(Registry::class);

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

/** @var ProductRepositoryInterface $productRepository */
$productRepository = $objectManager->get(ProductRepositoryInterface::class);
/** @var SearchCriteriaBuilder $searchCriteriaBuilder */
$searchCriteriaBuilder = $objectManager->get(SearchCriteriaBuilder::class);

$searchCriteria = $searchCriteriaBuilder->addFilter('sku', '35.42%', 'like')->create();
$searchResults = $productRepository->getList($searchCriteria);

foreach ($searchResults->getItems() as $product) {
    try {
        $productRepository->delete($product);
    } catch (NoSuchEntityException $exception) {
        //Product already removed
    }
}

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
