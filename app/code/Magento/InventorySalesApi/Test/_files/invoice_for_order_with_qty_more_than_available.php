<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Framework\DB\Transaction;
use Magento\Sales\Api\InvoiceManagementInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;

require __DIR__ . '/../../../../../../dev/tests/integration/testsuite/Magento/Sales/_files/default_rollback.php';
require 'order_with_qty_more_than_available.php';
/** @var \Magento\Sales\Model\Order $order */

$orderService = ObjectManager::getInstance()->create(InvoiceManagementInterface::class);
$invoice = $orderService->prepareInvoice($order);
$invoice->register();

$order = $invoice->getOrder();
$order->setIsInProcess(true);

$transactionSave = Bootstrap::getObjectManager()->create(Transaction::class);
$transactionSave->addObject($invoice)->addObject($order)->save();
