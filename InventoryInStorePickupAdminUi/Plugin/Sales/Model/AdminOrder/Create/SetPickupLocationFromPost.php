<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\InventoryInStorePickupAdminUi\Plugin\Sales\Model\AdminOrder\Create;

use Magento\Backend\Model\Session\Quote;
use Magento\Framework\App\RequestInterface;
use Magento\InventoryInStorePickupQuote\Model\Address\SetAddressPickupLocation;
use Magento\InventoryInStorePickupShippingApi\Model\Carrier\InStorePickup;
use Magento\Sales\Controller\Adminhtml\Order\Create\Save;

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
     * @var Quote
     */
    private $backendQuote;

    /**
     * @param RequestInterface $request
     * @param SetAddressPickupLocation $setAddressPickupLocation
     * @param Quote $backendQuote
     */
    public function __construct(
        RequestInterface $request,
        SetAddressPickupLocation $setAddressPickupLocation,
        Quote $backendQuote
    ) {
        $this->request = $request;
        $this->setAddressPickupLocation = $setAddressPickupLocation;
        $this->backendQuote = $backendQuote;
    }

    /**
     * @param Save $subject
     *
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeExecute(Save $subject)
    {
        $address = $this->backendQuote->getQuote()->getShippingAddress();
        if ($address->getShippingMethod() == InStorePickup::DELIVERY_METHOD) {
            $pickupLocationCode = $this->request->getParam(self::PARAM_KEY);
            $this->setAddressPickupLocation->execute($address, $pickupLocationCode);
        }
    }
}
