<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupSalesAdminUi\Plugin\Sales\Block\Adminhtml\Order;

use Magento\InventoryInStorePickupShippingApi\Model\Carrier\InStorePickup;
use Magento\Sales\Block\Adminhtml\Order\View;

/**
 * Process 'Ship' button considering 'Pick in Store' shipping method.
 */
class ProcessShipButtonPlugin
{
    /**
     * Remove 'Ship' button in case order shipping method is 'instore_pickup'.
     *
     * @param View $subject
     * @param \Closure $proceed
     * @param string $buttonId
     * @param array $data
     * @param int $level
     * @param int $sortOrder
     * @param string $region
     * @return View
     */
    public function aroundAddButton(
        View $subject,
        \Closure $proceed,
        string $buttonId,
        array $data,
        int $level = 0,
        int $sortOrder = 0,
        string $region = 'toolbar'
    ): View {
        if ($buttonId === 'order_ship') {
            if ($subject->getOrder()->getShippingMethod() === InStorePickup::DELIVERY_METHOD) {
                return $subject;
            }
        }

        return $proceed($buttonId, $data, $level, $sortOrder, $region);
    }
}
