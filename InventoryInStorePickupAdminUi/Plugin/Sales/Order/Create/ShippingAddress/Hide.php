<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupAdminUi\Plugin\Sales\Order\Create\ShippingAddress;

use Closure;
use Magento\InventoryInStorePickupShippingApi\Model\IsInStorePickupDeliveryCartInterface;
use Magento\Quote\Model\Quote;
use Magento\Sales\Block\Adminhtml\Order\Create\Shipping\Address;

/**
 * Hide shipping address form for store pickup orders
 */
class Hide
{
    /**
     * @var IsInStorePickupDeliveryCartInterface
     */
    private $isInStorePickupDeliveryCart;

    /**
     * @param IsInStorePickupDeliveryCartInterface $isInStorePickupDeliveryCart
     */
    public function __construct(
        IsInStorePickupDeliveryCartInterface $isInStorePickupDeliveryCart
    ) {
        $this->isInStorePickupDeliveryCart = $isInStorePickupDeliveryCart;
    }

    /**
     * Return empty content if we use store pickup delivery
     *
     * @param Address $block
     * @param Closure $toHtml
     *
     * @return string
     */
    public function aroundToHtml(Address $block, Closure $toHtml): string
    {
        $quote = $block->getQuote();
        $this->updateShippingMethod($quote);
        if ($this->isInStorePickupDeliveryCart->execute($quote)) {
            return '';
        }

        return $toHtml();
    }

    /**
     * Set the shipping method to shipping assignments.
     * In case of admin, it can be not up to date with address->getShippingMethod().
     *
     * @param Quote $quote
     *
     * @return void
     */
    private function updateShippingMethod(Quote $quote): void
    {
        if (!$quote->getExtensionAttributes() || !$quote->getExtensionAttributes()->getShippingAssignments()) {
            return;
        }

        $shippingAssignment = current($quote->getExtensionAttributes()->getShippingAssignments());
        $shippingAssignment->getShipping()
                           ->setMethod(
                               $quote->getShippingAddress()->getShippingMethod()
                           );
    }
}
