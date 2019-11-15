<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\InventoryInStorePickupShippingApi\Model\Carrier\InStorePickup;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\AddressExtensionFactory;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\AddressInterfaceFactory;
use Magento\Quote\Api\Data\CartExtensionFactory;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Api\Data\ShippingAssignmentInterfaceFactory;
use Magento\Quote\Api\Data\ShippingInterface;
use Magento\Quote\Api\Data\ShippingInterfaceFactory;
use Magento\Quote\Model\QuoteIdMaskFactory;
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
/** @var \Magento\Quote\Api\Data\CartExtensionFactory $quoteExtensionFactory */
$quoteExtensionFactory = Bootstrap::getObjectManager()->get(CartExtensionFactory::class);
/** @var ShippingAssignmentInterfaceFactory $shippingAssignmentFactory */
$shippingAssignmentFactory = Bootstrap::getObjectManager()->get(ShippingAssignmentInterfaceFactory::class);
/** @var ShippingInterfaceFactory $shippingFactory */
$shippingFactory = Bootstrap::getObjectManager()->get(ShippingInterfaceFactory::class);
/** @var \Magento\Quote\Api\Data\AddressExtensionFactory $addressExtensionFactory */
$addressExtensionFactory = Bootstrap::getObjectManager()->get(AddressExtensionFactory::class);

$cartId = $cartManagement->createEmptyCart();
$cart = $cartRepository->get($cartId);
$cart->setCustomerEmail('admin@example.com');
$cart->setCustomerIsGuest(true);
$cart->setReservedOrderId('in_store_pickup_test_order');

$store = $storeRepository->get('store_for_eu_website');
$cart->setStoreId($store->getId());
$storeManager->setCurrentStore($store->getCode());

/** @var ProductRepositoryInterface $productRepository */
$productRepository = Bootstrap::getObjectManager()->get(ProductRepositoryInterface::class);

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

$cartRepository->save($cart);
$cart = $cartRepository->get($cart->getId());

$addressExtension = $addressExtensionFactory->create();
$addressExtension->setPickupLocationCode('eu-1');
$address->setExtensionAttributes($addressExtension);

$cart->setBillingAddress($address);
$cart->setShippingAddress($address);
$cart->getPayment()->setMethod('checkmo');

/** @var ShippingInterface $shipping */
$shipping = $shippingFactory->create();
$shipping->setAddress($address);
$shipping->setMethod(InStorePickup::DELIVERY_METHOD);
/** @var ShippingAssignmentInterface $shippingAssignment */
$shippingAssignment = $shippingAssignmentFactory->create();
$shippingAssignment->setShipping($shipping);
$shippingAssignment->setItems($cart->getItems());

if (!$cart->getExtensionAttributes()) {
    $cart->setExtensionAttributes($quoteExtensionFactory->create());
}

$cart->getExtensionAttributes()->setShippingAssignments([$shippingAssignment]);

$cartRepository->save($cart);

/** @var \Magento\Quote\Model\QuoteIdMask $quoteIdMask */
$quoteIdMask = Bootstrap::getObjectManager()->create(QuoteIdMaskFactory::class)->create();
$quoteIdMask->setQuoteId($cart->getId());
$quoteIdMask->setDataChanges(true);
$quoteIdMask->save();
