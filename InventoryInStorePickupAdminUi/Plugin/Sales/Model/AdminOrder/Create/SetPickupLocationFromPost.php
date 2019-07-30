<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\InventoryInStorePickupAdminUi\Plugin\Sales\Model\AdminOrder\Create;

use Magento\Backend\Model\Session\Quote;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryInStorePickupAdminUi\Model\GetShippingAddressBySourceCodeAndOriginalAddress;
use Magento\InventoryInStorePickupQuote\Model\Address\SetAddressPickupLocation;
use Magento\InventoryInStorePickupShippingApi\Model\Carrier\InStorePickup;
use Magento\Sales\Controller\Adminhtml\Order\Create\Save;

/**
 * Set shipping address from POST
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
     * @var GetShippingAddressBySourceCodeAndOriginalAddress
     */
    private $getShippingAddressBySourceCodeAndOriginalAddress;

    /**
     * @param RequestInterface $request
     * @param SetAddressPickupLocation $setAddressPickupLocation
     * @param Quote $backendQuote
     * @param GetShippingAddressBySourceCodeAndOriginalAddress $getShippingAddressBySourceCodeAndOriginalAddress
     */
    public function __construct(
        RequestInterface $request,
        SetAddressPickupLocation $setAddressPickupLocation,
        Quote $backendQuote,
        GetShippingAddressBySourceCodeAndOriginalAddress $getShippingAddressBySourceCodeAndOriginalAddress
    ) {
        $this->request = $request;
        $this->setAddressPickupLocation = $setAddressPickupLocation;
        $this->backendQuote = $backendQuote;
        $this->getShippingAddressBySourceCodeAndOriginalAddress = $getShippingAddressBySourceCodeAndOriginalAddress;
    }

    /**
     * Add pickup location code to the shipping address
     *
     * @param Save $subject
     *
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @throws NoSuchEntityException
     */
    public function beforeExecute(Save $subject)
    {
        $quote = $this->backendQuote->getQuote();
        $address = $quote->getShippingAddress();
        $pickupLocationCode = $this->request->getParam(self::PARAM_KEY);

        if ($address->getShippingMethod() == InStorePickup::DELIVERY_METHOD && $pickupLocationCode) {
            $this->setAddressPickupLocation->execute($address, $pickupLocationCode);
            $quote->setShippingAddress(
                $this->getShippingAddressBySourceCodeAndOriginalAddress->execute($pickupLocationCode, $address)
            );
        }
    }
}
