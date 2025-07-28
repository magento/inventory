<?php
/**
 * Copyright 2025 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupQuote\Plugin\Quote;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryInStorePickup\Model\ExtractPickupLocationAddressData;
use Magento\InventoryInStorePickupApi\Model\GetPickupLocationInterface;
use Magento\InventoryInStorePickupQuote\Model\IsPickupLocationShippingAddress;
use Magento\InventoryInStorePickupQuote\Model\GetShippingAddressData;
use Magento\InventoryInStorePickupQuote\Model\GetWebsiteCodeByStoreId;
use Magento\InventoryInStorePickupShippingApi\Model\IsInStorePickupDeliveryCartInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ReplaceShippingAddressWithPickupLocationAddressOnAssignCustomer
{
    /**
     * @param IsInStorePickupDeliveryCartInterface $isInStorePickupDeliveryCart
     * @param GetPickupLocationInterface $getPickupLocation
     * @param ExtractPickupLocationAddressData $extractPickupLocationShippingAddressData
     * @param DataObjectHelper $dataObjectHelper
     * @param GetShippingAddressData $getShippingAddressData
     * @param IsPickupLocationShippingAddress $isPickupLocationShippingAddress
     * @param GetWebsiteCodeByStoreId $getWebsiteCodeByStoreId
     */
    public function __construct(
        private readonly IsInStorePickupDeliveryCartInterface $isInStorePickupDeliveryCart,
        private readonly GetPickupLocationInterface $getPickupLocation,
        private readonly ExtractPickupLocationAddressData $extractPickupLocationShippingAddressData,
        private readonly DataObjectHelper $dataObjectHelper,
        private readonly GetShippingAddressData $getShippingAddressData,
        private readonly IsPickupLocationShippingAddress $isPickupLocationShippingAddress,
        private readonly GetWebsiteCodeByStoreId $getWebsiteCodeByStoreId
    ) {
    }

    /**
     * Replace Shipping Address with Pickup Location Shipping Address for Quote when customer is assigned to Quote.
     *
     * The original method overrides the shipping address with customer default shipping address which results in
     * removing the pickup location address.
     *
     * @param Quote $quote
     * @param CustomerInterface $customer
     * @param Address|null $billingAddress
     * @param Address|null $shippingAddress
     * @return array
     */
    public function beforeAssignCustomerWithAddressChange(
        Quote $quote,
        CustomerInterface $customer,
        ?Address $billingAddress = null,
        ?Address $shippingAddress = null
    ): array {
        if (null === $shippingAddress
            && $this->isInStorePickupDeliveryCart->execute($quote)
            && $quote->getShippingAddress()?->getExtensionAttributes()?->getPickupLocationCode()
        ) {
            try {
                $location = $this->getPickupLocation->execute(
                    (string) $quote->getShippingAddress()->getExtensionAttributes()->getPickupLocationCode(),
                    SalesChannelInterface::TYPE_WEBSITE,
                    $this->getWebsiteCodeByStoreId->execute((int)$quote->getStoreId())
                );
            } catch (NoSuchEntityException $e) {  // phpcs:ignore Magento2.CodeAnalysis.EmptyBlock.DetectedCatch
                // Pickup location is not found or is invalid for this store
            }
            if (isset($location)) {
                $shippingAddress = $quote->getShippingAddress();
                if (!$this->isPickupLocationShippingAddress->execute($location, $shippingAddress)) {
                    $pickupLocationAddressData = $this->getShippingAddressData->execute()
                        + $this->extractPickupLocationShippingAddressData->execute($location);

                    $this->dataObjectHelper->populateWithArray(
                        $shippingAddress,
                        $pickupLocationAddressData,
                        AddressInterface::class
                    );
                }
            }
        }
        return [$customer, $billingAddress, $shippingAddress];
    }
}
