<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupShipping\Model;

use Magento\InventoryInStorePickupShippingApi\Model\Carrier\InStorePickup;
use Magento\InventoryInStorePickupShippingApi\Model\IsInStorePickupDeliveryCartInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Api\Data\ShippingInterface;

/**
 * @inheritdoc
 */
class IsInStorePickupDeliveryCart implements IsInStorePickupDeliveryCartInterface
{
    /**
     * @inheritdoc
     */
    public function execute(CartInterface $cart): bool
    {
        if (!$cart->getExtensionAttributes() || !$cart->getExtensionAttributes()->getShippingAssignments()) {
            return false;
        }

        $shippingAssignments = $cart->getExtensionAttributes()->getShippingAssignments();
        /** @var ShippingAssignmentInterface $shippingAssignment */
        $shippingAssignment = current($shippingAssignments);
        /** @var ShippingInterface $shipping */
        $shipping = $shippingAssignment->getShipping();

        return $shipping->getMethod() === InStorePickup::DELIVERY_METHOD;
    }
}
