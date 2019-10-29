<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupShippingAdminUi\Plugin\Shipping\Controller\Order\Shipment\View;

use Magento\Framework\Registry;
use Magento\Framework\View\Result\Page;
use Magento\InventoryInStorePickupShippingApi\Model\Carrier\InStorePickup;
use Magento\Shipping\Controller\Adminhtml\Order\Shipment\View;

/**
 * Remove tracking information from order shipment view page.
 */
class ShipmentTrackingPlugin
{
    private const SHIPMENT_TRACKING_BLOCK_NAME = 'shipment_tracking';

    /**
     * @var Registry
     */
    private $registry;

    /**
     * @param Registry $registry
     */
    public function __construct(Registry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * Remove tracing information block in case delivery method is pick in store.
     *
     * @param View $subject
     * @param Page $result
     * @return Page
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterExecute(View $subject, $result)
    {
        if ($result instanceof Page) {
            $shipment = $this->registry->registry('current_shipment');
            if ($shipment->getOrder()->getShippingMethod() === InStorePickup::DELIVERY_METHOD) {
                $result->getLayout()->unsetElement(self::SHIPMENT_TRACKING_BLOCK_NAME);
            }
        }

        return $result;
    }
}
