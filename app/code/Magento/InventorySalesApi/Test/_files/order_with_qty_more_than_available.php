<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Item;
use Magento\Sales\Model\Order\Payment;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;

require __DIR__ . '/../../../../../../dev/tests/integration/testsuite/Magento/Sales/_files/default_rollback.php';
require __DIR__ . '/../../../../../../dev/tests/integration/testsuite/Magento/Catalog/_files/product_simple.php';
/** @var \Magento\Catalog\Model\Product $product */

$objectManager = Bootstrap::getObjectManager();

$payment = $objectManager->create(Payment::class);
$payment->setMethod('checkmo');
$payment->setAdditionalInformation('last_trans_id', '11122');
$payment->setAdditionalInformation(
    'metadata',
    [
        'type' => 'free',
        'fraudulent' => false
    ]
);

/** @var Item $orderItem */
$orderItem = $objectManager->create(Item::class);
$orderItem->setProductId($product->getId())->setQtyOrdered(102);
$orderItem->setBasePrice($product->getPrice());
$orderItem->setPrice($product->getPrice());
$orderItem->setRowTotal($product->getPrice());
$orderItem->setProductType('simple');
$orderItem->setSku($product->getSku());

/** @var Order $order */
$order = $objectManager->create(Order::class);
$order->setIncrementId(
    '100000001'
)->setState(
    Order::STATE_PROCESSING
)->setStatus(
    $order->getConfig()->getStateDefaultStatus(Order::STATE_PROCESSING)
)->setSubtotal(
    100
)->setGrandTotal(
    100
)->setBaseSubtotal(
    100
)->setBaseGrandTotal(
    100
)->setCustomerIsGuest(
    true
)->setCustomerEmail(
    'customer@null.com'
)->setStoreId(
    $objectManager->get(StoreManagerInterface::class)->getStore()->getId()
)->addItem(
    $orderItem
)->setPayment(
    $payment
);
$order->save();
