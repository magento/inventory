<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\InventoryInStorePickupQuote\Plugin\Quote\Address;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryInStorePickup\Model\PickupLocation\GetPickupLocationByCode;
use Magento\InventoryInStorePickupQuote\Model\Address\GetAddressPickupLocationCode;
use Magento\InventoryInStorePickupShippingApi\Model\Carrier\GetCarrierTitle;
use Magento\InventoryInStorePickupShippingApi\Model\Carrier\InStorePickup;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\Quote\Model\Quote\TotalsCollector;

/**
 * Set Shipping Description
 * e.g. In-Store Pickup Delivery - Pickup Location Name
 */
class SetShippingDescription
{
    /**
     * @var GetAddressPickupLocationCode
     */
    private $getAddressPickupLocationCode;

    /**
     * @var GetCarrierTitle
     */
    private $getCarrierTitle;

    /**
     * @var GetPickupLocationByCode
     */
    private $getPickupLocationByCode;

    /**
     * @param GetAddressPickupLocationCode $getAddressPickupLocationCode
     * @param GetCarrierTitle $getCarrierTitle
     * @param GetPickupLocationByCode $getPickupLocationByCode
     */
    public function __construct(
        GetAddressPickupLocationCode $getAddressPickupLocationCode,
        GetCarrierTitle $getCarrierTitle,
        GetPickupLocationByCode $getPickupLocationByCode
    ) {
        $this->getAddressPickupLocationCode = $getAddressPickupLocationCode;
        $this->getCarrierTitle = $getCarrierTitle;
        $this->getPickupLocationByCode = $getPickupLocationByCode;
    }

    /**
     * @param TotalsCollector $subject
     * @param Total $total
     * @param Quote $quote
     *
     * @return Total
     * @throws NoSuchEntityException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterCollect(
        TotalsCollector $subject,
        Total $total,
        Quote $quote
    ) {
        $address = $quote->getShippingAddress();
        if (
            $address->getShippingMethod() == InStorePickup::DELIVERY_METHOD
            && $this->getAddressPickupLocationCode->execute($address)
        ) {
            $description = $this->getShippingDescription($this->getAddressPickupLocationCode->execute($address));
            $total->setShippingDescription($description);
            foreach ($quote->getAllAddresses() as $address) {
                $address->setShippingDescription($description);
            }
        }

        return $total;
    }

    /**
     * @param string $pickupLocationCode
     *
     * @return string
     * @throws NoSuchEntityException
     */
    private function getShippingDescription(string $pickupLocationCode): string
    {
        $pickupLocationName = $this->getPickupLocationByCode->execute($pickupLocationCode)->getName();
        $carrierTitle = $this->getCarrierTitle->execute();

        return "$carrierTitle - $pickupLocationName";
    }
}
