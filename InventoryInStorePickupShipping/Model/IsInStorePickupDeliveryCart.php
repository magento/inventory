<?php
/**
 * Copyright 2019 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupShipping\Model;

use Magento\InventoryInStorePickupShippingApi\Model\Carrier\InStorePickup;
use Magento\InventoryInStorePickupShippingApi\Model\IsInStorePickupDeliveryCartInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote;

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
        if (!$cart->getShippingAddress() || !$cart->getShippingAddress()->getShippingMethod()) {
            return false;
        }

        /** @var Quote $cart */
        return $cart->getShippingAddress()->getShippingMethod() === InStorePickup::DELIVERY_METHOD;
    }
}
