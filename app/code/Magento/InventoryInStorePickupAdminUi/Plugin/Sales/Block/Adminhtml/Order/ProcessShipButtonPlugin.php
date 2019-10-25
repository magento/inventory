<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupAdminUi\Plugin\Sales\Block\Adminhtml\Order;

use Magento\Sales\Block\Adminhtml\Order\View;

/**
 * Process 'Ship' button considering 'Pick in Store' shipping method.
 */
class ProcessShipButtonPlugin
{
    /**
     * Remove 'Ship' button in case order shipping method is 'in_store_pickup'.
     *
     * @param View $subject
     * @param View $result
     * @param string $buttonId
     * @return View
     */
    public function afterAddButton(View $subject, View $result, $buttonId): View
    {
        if ($buttonId === 'order_ship') {
            if ($subject->getOrder()->getShippingMethod() === 'in_store_pickup') {
                $subject->removeButton('order_ship');
            }
        }

        return $result;
    }
}
