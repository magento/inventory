<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\DataObject;
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

foreach (['SKU-1', 'SKU-2', 'SKU-3', 'SKU-4', 'SKU-6'] as $sku) {
    $searchCriteria = $searchCriteriaBuilder
        ->addFilter('reserved_order_id', 'in_store_pickup_test_order-' . $sku)
        ->create();
    $cart = current($cartRepository->getList($searchCriteria)->getItems());

    $product = $productRepository->get($sku);
    $requestData = [
        'product' => $product->getProductId(),
        'qty' => 1
    ];
    $request = new DataObject($requestData);
    try {
        $cart->addProduct($product, $request);
    } catch (\Exception $e) {
        $zzz = 1;
    }

    $cartRepository->save($cart);
    $cartManagement->placeOrder($cart->getId());
}
