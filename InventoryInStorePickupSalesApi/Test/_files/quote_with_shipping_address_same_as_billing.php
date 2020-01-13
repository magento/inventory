<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Quote\Api\CartRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;

include __DIR__ . '/create_in_store_pickup_quote_on_eu_website_customer.php';

$address = current($cart->getExtensionAttributes()->getShippingAssignments())->getShipping()->getAddress();
$address->setSameAsBilling(1);
$cartRepository = Bootstrap::getObjectManager()->get(CartRepositoryInterface::class);
$cartRepository->save($cart);
