<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;

/** @var SearchCriteriaBuilder $searchCriteriaBuilder */
$searchCriteriaBuilder = Bootstrap::getObjectManager()->get(SearchCriteriaBuilder::class);
/** @var CartRepositoryInterface $cartRepository */
$cartRepository = Bootstrap::getObjectManager()->get(CartRepositoryInterface::class);
/** @var CartManagementInterface $cartManagement */
$cartManagement = Bootstrap::getObjectManager()->get(CartManagementInterface::class);
/** @var StoreRepositoryInterface $storeRepository */
$storeRepository = Bootstrap::getObjectManager()->get(StoreRepositoryInterface::class);
/** @var StoreManagerInterface\ $storeManager */
$storeManager = Bootstrap::getObjectManager()->get(StoreManagerInterface::class);

$cartId = $cartManagement->createEmptyCart();
$cart = $cartRepository->get($cartId);
$cart->setCustomerEmail('admin@example.com');
$cart->setCustomerIsGuest(true);
$store = $storeRepository->getActiveStoreByCode('store_for_eu_website');
$cart->setStoreId($store->getId());
$storeManager->setCurrentStore($store->getCode());

require_once 'quote_shipping_address.php';

$cartRepository->save($cart);
