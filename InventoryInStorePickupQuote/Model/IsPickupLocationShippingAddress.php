<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupQuote\Model;

use Magento\Framework\App\ObjectManager;
use Magento\InventoryInStorePickup\Model\ExtractPickupLocationAddressData;
use Magento\InventoryInStorePickup\Model\PickupLocation\DataResolver as PickupLocationDataResolver;
use Magento\InventoryInStorePickupApi\Api\Data\PickupLocationInterface;
use Magento\Quote\Api\Data\AddressInterface;

/**
 * Check if provided Shipping Address is address of Pickup Location.
 */
class IsPickupLocationShippingAddress
{
    /**
     * @var ExtractPickupLocationAddressData
     */
    private $extractPickupLocationShippingAddressData;

    /**
     * @var ExtractQuoteAddressShippingAddressData
     */
    private $extractQuoteAddressShippingAddressData;

    /**
     * @var GetShippingAddressData
     */
    private $getShippingAddressData;

    /**
     * @var PickupLocationDataResolver
     */
    private $pickupLocationDataResolver;

    /**
     * @param ExtractPickupLocationAddressData $extractPickupLocationShippingAddressData
     * @param ExtractQuoteAddressShippingAddressData $extractQuoteAddressShippingAddressData
     * @param GetShippingAddressData $getShippingAddressData
     * @param PickupLocationDataResolver|null $pickupLocationDataResolver
     */
    public function __construct(
        ExtractPickupLocationAddressData $extractPickupLocationShippingAddressData,
        ExtractQuoteAddressShippingAddressData $extractQuoteAddressShippingAddressData,
        GetShippingAddressData $getShippingAddressData,
        ?PickupLocationDataResolver $pickupLocationDataResolver = null
    ) {
        $this->extractPickupLocationShippingAddressData = $extractPickupLocationShippingAddressData;
        $this->extractQuoteAddressShippingAddressData = $extractQuoteAddressShippingAddressData;
        $this->getShippingAddressData = $getShippingAddressData;
        $this->pickupLocationDataResolver = $pickupLocationDataResolver ?:
            ObjectManager::getInstance()->get(PickupLocationDataResolver::class);
    }

    /**
     * Check if Address is Pickup Location address.
     *
     * @param PickupLocationInterface $pickupLocation
     * @param AddressInterface $shippingAddress
     *
     * @return bool
     */
    public function execute(PickupLocationInterface $pickupLocation, AddressInterface $shippingAddress): bool
    {
        $data = $this->getShippingAddressData->execute() +
            $this->extractPickupLocationShippingAddressData->execute($pickupLocation);
        $data = $this->pickupLocationDataResolver->execute($pickupLocation, $data);

        if (!$shippingAddress->getExtensionAttributes() ||
            !$shippingAddress->getExtensionAttributes()->getPickupLocationCode()
        ) {
            return false;
        }

        $shippingAddressData = $this->extractQuoteAddressShippingAddressData->execute($shippingAddress);

        foreach ($data as $key => $value) {
            if (!array_key_exists($key, $shippingAddressData) || $shippingAddressData[$key] != $value) {
                return false;
            }
        }

        return true;
    }
}
