<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupQuote\Plugin\Quote\Address;

use Magento\InventoryInStorePickupQuote\Model\ResourceModel\DeleteQuoteAddressPickupLocation;
use Magento\InventoryInStorePickupQuote\Model\ResourceModel\SaveQuoteAddressPickupLocation;
use Magento\InventoryInStorePickupShippingApi\Model\IsInStorePickupDeliveryCartInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote\Address;

/**
 * Save or delete selected Pickup Location Code for Quote Address.
 */
class ManageAssignmentOfPickupLocationToQuoteAddress
{
    /**
     * @var SaveQuoteAddressPickupLocation
     */
    private $saveQuoteAddressPickupLocation;

    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * @var DeleteQuoteAddressPickupLocation
     */
    private $deleteQuoteAddressPickupLocation;

    /**
     * @var IsInStorePickupDeliveryCartInterface
     */
    private $isInStorePickupDeliveryCart;

    /**
     * @param SaveQuoteAddressPickupLocation $saveQuoteAddressPickupLocation
     * @param DeleteQuoteAddressPickupLocation $deleteQuoteAddressPickupLocation
     * @param CartRepositoryInterface $cartRepository
     * @param IsInStorePickupDeliveryCartInterface $isInStorePickupDeliveryCart
     */
    public function __construct(
        SaveQuoteAddressPickupLocation $saveQuoteAddressPickupLocation,
        DeleteQuoteAddressPickupLocation $deleteQuoteAddressPickupLocation,
        CartRepositoryInterface $cartRepository,
        IsInStorePickupDeliveryCartInterface $isInStorePickupDeliveryCart
    ) {
        $this->saveQuoteAddressPickupLocation = $saveQuoteAddressPickupLocation;
        $this->cartRepository = $cartRepository;
        $this->deleteQuoteAddressPickupLocation = $deleteQuoteAddressPickupLocation;
        $this->isInStorePickupDeliveryCart = $isInStorePickupDeliveryCart;
    }

    /**
     * Save information about associate Pickup Location Code to Quote Address.
     *
     * @param Address $subject
     * @param Address $result
     *
     * @return Address
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterAfterSave(Address $subject, Address $result): Address
    {
        if (!$this->validateAddress($subject)) {
            return $result;
        }

        $quote = $this->cartRepository->get((int)$subject->getQuoteId());

        if (!$this->isInStorePickupDeliveryCart->execute($quote) ||
            !$subject->getExtensionAttributes()->getPickupLocationCode()
        ) {
            $this->deleteQuoteAddressPickupLocation->execute((int)$subject->getId());

            return $result;
        }

        $this->saveQuoteAddressPickupLocation->execute(
            (int)$subject->getId(),
            $subject->getExtensionAttributes()->getPickupLocationCode()
        );

        return $result;
    }

    /**
     * Check if address can have a Pickup Location.
     *
     * @param Address $address
     *
     * @return bool
     */
    private function validateAddress(Address $address): bool
    {
        return $address->getExtensionAttributes() && $address->getAddressType() === Address::ADDRESS_TYPE_SHIPPING;
    }
}
