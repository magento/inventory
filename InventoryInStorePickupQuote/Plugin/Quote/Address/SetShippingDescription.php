<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\InventoryInStorePickupQuote\Plugin\Quote\Address;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryInStorePickupApi\Model\GetPickupLocationInterface;
use Magento\InventoryInStorePickupQuote\Model\Address\GetAddressPickupLocationCode;
use Magento\InventoryInStorePickupShippingApi\Model\Carrier\GetCarrierTitle;
use Magento\InventoryInStorePickupShippingApi\Model\Carrier\InStorePickup;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\Quote\Model\Quote\TotalsCollector;

/**
 * Set Shipping Description e.g. In-Store Pickup Delivery - Pickup Location Name
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
     * @var GetPickupLocationInterface
     */
    private $getPickupLocation;

    /**
     * @param GetAddressPickupLocationCode $getAddressPickupLocationCode
     * @param GetCarrierTitle $getCarrierTitle
     * @param GetPickupLocationInterface $getPickupLocation
     */
    public function __construct(
        GetAddressPickupLocationCode $getAddressPickupLocationCode,
        GetCarrierTitle $getCarrierTitle,
        GetPickupLocationInterface $getPickupLocation
    ) {
        $this->getAddressPickupLocationCode = $getAddressPickupLocationCode;
        $this->getCarrierTitle = $getCarrierTitle;
        $this->getPickupLocation = $getPickupLocation;
    }

    /**
     * Set shipping description to the quote api.
     *
     * @param TotalsCollector $subject
     * @param Total $total
     * @param Quote $quote
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
        if ($address->getShippingMethod() === InStorePickup::DELIVERY_METHOD
            && $this->getAddressPickupLocationCode->execute($address)
        ) {
            $description = $this->getShippingDescription(
                $this->getAddressPickupLocationCode->execute($address),
                SalesChannelInterface::TYPE_WEBSITE,
                $quote->getStore()->getWebsite()->getCode()
            );
            $total->setShippingDescription($description);
            foreach ($quote->getAllAddresses() as $address) {
                $address->setShippingDescription($description);
            }
        }

        return $total;
    }

    /**
     * Format shipping description based on Pickup Location code.
     *
     * @param string $pickupLocationCode
     * @param string $salesChannelType
     * @param string $salesChannelCode
     *
     * @return string
     * @throws NoSuchEntityException
     */
    private function getShippingDescription(
        string $pickupLocationCode,
        string $salesChannelType,
        string $salesChannelCode
    ): string {
        $pickupLocationName = $this->getPickupLocation->execute(
            $pickupLocationCode,
            $salesChannelType,
            $salesChannelCode
        )->getName();
        $carrierTitle = $this->getCarrierTitle->execute();

        return "$carrierTitle - $pickupLocationName";
    }
}
