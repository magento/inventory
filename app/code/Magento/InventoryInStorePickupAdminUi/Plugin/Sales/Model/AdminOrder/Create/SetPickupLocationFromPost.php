<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\InventoryInStorePickupAdminUi\Plugin\Sales\Model\AdminOrder\Create;

use Magento\Framework\App\RequestInterface;
use Magento\InventoryInStorePickupShipping\Model\Address\SetAddressPickupLocation;
use Magento\InventoryInStorePickupShippingApi\Model\Carrier\InStorePickup;
use Magento\Sales\Model\AdminOrder\Create;

/**
 * Set shipping address location from POST
 */
class SetPickupLocationFromPost
{
    private const PARAM_KEY = 'pickup_location_source';

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var SetAddressPickupLocation
     */
    private $setAddressPickupLocation;

    /**
     * @param RequestInterface $request
     * @param SetAddressPickupLocation $setAddressPickupLocation
     */
    public function __construct(
        RequestInterface $request,
        SetAddressPickupLocation $setAddressPickupLocation
    ) {
        $this->request = $request;
        $this->setAddressPickupLocation = $setAddressPickupLocation;
    }

    /**
     * @param Create $subject
     *
     * @return void
     */
    public function beforeCreateOrder(Create $subject)
    {
        $address = $subject->getShippingAddress();
        if ($address->getShippingMethod() == InStorePickup::DELIVERY_METHOD) {
            $pickupLocationCode = $this->request->getParam(self::PARAM_KEY);
            $this->setAddressPickupLocation->execute($address, $pickupLocationCode);
        }
    }
}
