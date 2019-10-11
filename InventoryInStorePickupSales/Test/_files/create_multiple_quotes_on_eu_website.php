<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\AddressInterfaceFactory;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;

/** @var CartRepositoryInterface $cartRepository */
$cartRepository = Bootstrap::getObjectManager()->get(CartRepositoryInterface::class);
/** @var CartManagementInterface $cartManagement */
$cartManagement = Bootstrap::getObjectManager()->get(CartManagementInterface::class);
/** @var AddressInterfaceFactory $addressFactory */
$addressFactory = Bootstrap::getObjectManager()->get(AddressInterfaceFactory::class);
/** @var StoreRepositoryInterface $storeRepository */
$storeRepository = Bootstrap::getObjectManager()->get(StoreRepositoryInterface::class);
/** @var StoreManagerInterface\ $storeManager */
$storeManager = Bootstrap::getObjectManager()->get(StoreManagerInterface::class);

$store = $storeRepository->get('store_for_eu_website');
$storeManager->setCurrentStore($store->getCode());

/** @var AddressInterface $address */
$address = $addressFactory->create(
    [
        'data' => [
            AddressInterface::KEY_COUNTRY_ID => 'US',
            AddressInterface::KEY_REGION_ID => 15,
            AddressInterface::KEY_LASTNAME => 'Doe',
            AddressInterface::KEY_FIRSTNAME => 'John',
            AddressInterface::KEY_STREET => 'example street',
            AddressInterface::KEY_EMAIL => 'customer@example.com',
            AddressInterface::KEY_CITY => 'Los Angeles',
            AddressInterface::KEY_TELEPHONE => '937 99 92',
            AddressInterface::KEY_POSTCODE => 12345
        ]
    ]
);
foreach (['SKU-1', 'SKU-2', 'SKU-3', 'SKU-4', 'SKU-6'] as $sku) {
    $cartId = $cartManagement->createEmptyCart();
    $cart = $cartRepository->get($cartId)
                           ->setCustomerEmail('admin@example.com')
                           ->setCustomerIsGuest(true)
                           ->setStoreId($store->getId())
                           ->setReservedOrderId('in_store_pickup_test_order-' . $sku)
                           ->setBillingAddress($address)
                           ->setShippingAddress($address);
    $cart->getPayment()->setMethod('checkmo');
    /** Will be replaced with 'In Store Pickup' delivery method */
    $cart->getShippingAddress()->setShippingMethod('flatrate_flatrate');
    $cart->getShippingAddress()->setCollectShippingRates(true);
    $cart->getShippingAddress()->collectShippingRates();
    $cartRepository->save($cart);
}
