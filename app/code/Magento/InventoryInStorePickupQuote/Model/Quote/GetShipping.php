<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupQuote\Model\Quote;

use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Api\Data\ShippingInterface;

/**
 * Extract Shipping from Quote.
 */
class GetShipping
{
    /**
     * Extract Shipping from Quote.
     *
     * @param CartInterface $quote
     *
     * @return ShippingInterface|null
     */
    public function execute(CartInterface $quote): ?ShippingInterface
    {
        if (!$quote->getExtensionAttributes() || !$quote->getExtensionAttributes()->getShippingAssignments()) {
            return null;
        }

        $shippingAssignments = $quote->getExtensionAttributes()->getShippingAssignments();
        /** @var ShippingAssignmentInterface $shippingAssignment */
        $shippingAssignment = current($shippingAssignments);

        return $shippingAssignment->getShipping();
    }
}
