<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupSalesAdminUi\Plugin\Sales\Block\Adminhtml\Order\Invoice\Create;

use Magento\InventoryInStorePickupShippingApi\Model\Carrier\InStorePickup;
use Magento\Sales\Block\Adminhtml\Order\Invoice\Create\Form;

/**
 * Process 'Create Shipment' checkbox on invoice create page.
 */
class ProcessCreateShipment
{
    /**
     * Hide 'Create Shipment' checkbox in case delivery method is 'pick in store'.
     *
     * @param Form $subject
     * @param bool $result
     * @return bool
     */
    public function afterCanCreateShipment(Form $subject, bool $result): bool
    {
        if ($subject->getOrder()->getShippingMethod() === InStorePickup::DELIVERY_METHOD) {
            $result = false;
        }

        return $result;
    }
}
