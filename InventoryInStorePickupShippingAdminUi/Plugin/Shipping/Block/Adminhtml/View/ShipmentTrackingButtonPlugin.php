<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupShippingAdminUi\Plugin\Shipping\Block\Adminhtml\View;

use Magento\InventoryInStorePickupShippingApi\Model\Carrier\InStorePickup;
use Magento\Shipping\Block\Adminhtml\View;

/**
 * 'Send tracking information' button processor.
 */
class ShipmentTrackingButtonPlugin
{
    /**
     * Remove 'Send tracking information' button in case shipping method is store pickup.
     *
     * @param View $subject
     * @return void
     */
    public function beforeSetLayout(View $subject): void
    {
        if ($subject->getShipment()->getOrder()->getShippingMethod() === InStorePickup::DELIVERY_METHOD) {
            $subject->removeButton('save');
        }
    }
}
