<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;

/** @var SearchCriteriaBuilder $searchCriteriaBuilder */
$searchCriteriaBuilder = Bootstrap::getObjectManager()->get(SearchCriteriaBuilder::class);
/** @var CartRepositoryInterface $cartRepository */
$cartRepository = Bootstrap::getObjectManager()->get(CartRepositoryInterface::class);
/** @var ProductRepositoryInterface $productRepository */
$productRepository = Bootstrap::getObjectManager()->get(ProductRepositoryInterface::class);
/** @var CartManagementInterface $cartManagement */
$cartManagement = Bootstrap::getObjectManager()->get(CartManagementInterface::class);

$searchCriteria = $searchCriteriaBuilder
    ->addFilter('reserved_order_id', 'in_store_pickup_test_order')
    ->create();
$cart = current($cartRepository->getList($searchCriteria)->getItems());

$itemsToAdd = [
    'SKU-1' => 3.5,
    'SKU-2' => 2
];

foreach ($itemsToAdd as $sku => $qty) {
    $product = $productRepository->get($sku);
    $requestData = [
        'product' => $product->getProductId(),
        'qty' => $qty
    ];
    $request = new \Magento\Framework\DataObject($requestData);
    $cart->addProduct($product, $request);
}

$cart->getShippingAddress()->setCollectShippingRates(true);
$cart->getShippingAddress()->collectShippingRates();
$cartRepository->save($cart);
