<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\DB\Transaction;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Address as OrderAddress;
use Magento\Sales\Model\Order\Item as OrderItem;
use Magento\Sales\Model\Order\Payment;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

Resolver::getInstance()->requireDataFixture('Magento/Sales/_files/default_rollback.php');
Resolver::getInstance()->requireDataFixture('Magento/Catalog/_files/product_simple.php');

/** @var $objectManager ObjectManager */
$objectManager = Bootstrap::getObjectManager();

/** @var ProductRepositoryInterface $productRepository */
$productRepository = $objectManager->create(ProductRepositoryInterface::class);
$product = $productRepository->get('simple');

$addressData = include __DIR__ .
    '/../../../../../../../dev/tests/integration/testsuite/Magento/Sales/_files/address_data.php';

/** @var OrderManagementInterface $orderManagement */
$orderManagement = $objectManager->create(OrderManagementInterface::class);
/** @var Transaction $transaction */
$transaction = $objectManager->create(Transaction::class);

for ($i = 1; $i <= 3; $i++) {
    $billingAddress = $objectManager->create(OrderAddress::class, ['data' => $addressData]);
    $billingAddress->setAddressType('billing');

    $shippingAddress = clone $billingAddress;
    $shippingAddress->setId(null)->setAddressType('shipping');

    /** @var Payment $payment */
    $payment = $objectManager->create(Payment::class);
    $payment->setMethod('checkmo')
        ->setAdditionalInformation('last_trans_id', '11122')
        ->setAdditionalInformation(
            'metadata',
            [
                'type' => 'free',
                'fraudulent' => false,
            ]
        );

    /** @var OrderItem $orderItem */
    $orderItem = $objectManager->create(OrderItem::class);
    $orderItem->setProductId($product->getId())
        ->setQtyOrdered(2)
        ->setSku($product->getSku())
        ->setBasePrice($product->getPrice())
        ->setPrice($product->getPrice())
        ->setRowTotal($product->getPrice())
        ->setProductType('simple')
        ->setName($product->getName());

    /** @var Order $order */
    $order = $objectManager->create(Order::class);
    $order->setIncrementId('10000000' . $i)
        ->setState(Order::STATE_NEW)
        ->setStatus($order->getConfig()->getStateDefaultStatus(Order::STATE_NEW))
        ->setSubtotal(100)
        ->setGrandTotal(100)
        ->setBaseSubtotal(100)
        ->setBaseGrandTotal(100)
        ->setCustomerIsGuest(true)
        ->setCustomerEmail('customer@null.com')
        ->setBillingAddress($billingAddress)
        ->setShippingAddress($shippingAddress)
        ->setStoreId($objectManager->get(StoreManagerInterface::class)->getStore()->getId())
        ->addItem($orderItem)
        ->setPayment($payment);
    $orderManagement->place($order);
    $transaction->addObject($order)->save();
}
