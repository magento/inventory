<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupQuote\Plugin\Quote\Address;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryInStorePickupQuote\Model\ResourceModel\DeleteQuoteAddressPickupLocation;
use Magento\InventoryInStorePickupQuote\Model\ResourceModel\SaveQuoteAddressPickupLocation;
use Magento\InventoryInStorePickupShippingApi\Model\Carrier\InStorePickup;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Model\Quote\Address as AddressEntity;
use Magento\Quote\Model\ResourceModel\Quote\Address;

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
     * @param SaveQuoteAddressPickupLocation $saveQuoteAddressPickupLocation
     * @param DeleteQuoteAddressPickupLocation $deleteQuoteAddressPickupLocation
     * @param CartRepositoryInterface $cartRepository
     */
    public function __construct(
        SaveQuoteAddressPickupLocation $saveQuoteAddressPickupLocation,
        DeleteQuoteAddressPickupLocation $deleteQuoteAddressPickupLocation,
        CartRepositoryInterface $cartRepository
    ) {
        $this->saveQuoteAddressPickupLocation = $saveQuoteAddressPickupLocation;
        $this->cartRepository = $cartRepository;
        $this->deleteQuoteAddressPickupLocation = $deleteQuoteAddressPickupLocation;
    }

    /**
     * Save information about associate Pickup Location Code to Quote Address.
     *
     * @param Address $subject
     * @param Address $result
     * @param AddressEntity $entity
     *
     * @return Address
     * @throws NoSuchEntityException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterSave(Address $subject, Address $result, AddressEntity $entity): Address
    {
        $quote = $this->cartRepository->get($entity->getQuoteId());

        if (!$entity->getExtensionAttributes() ||
            !$quote->getExtensionAttributes() ||
            !$quote->getExtensionAttributes()->getShippingAssignments() ||
            !($entity->getAddressType() === AddressEntity::ADDRESS_TYPE_SHIPPING)
        ) {
            return $result;
        }

        $shippingAssignments = $quote->getExtensionAttributes()->getShippingAssignments();

        /** @var ShippingAssignmentInterface $shippingAssignment */
        $shippingAssignment = current($shippingAssignments);
        $shipping = $shippingAssignment->getShipping();

        if (!($shipping->getMethod() === InStorePickup::DELIVERY_METHOD &&
            $entity->getExtensionAttributes()->getPickupLocationCode())
        ) {
            $this->deleteQuoteAddressPickupLocation->execute((int)$entity->getId());

            return $result;
        }

        $this->saveQuoteAddressPickupLocation->execute(
            (int)$entity->getId(),
            $entity->getExtensionAttributes()->getPickupLocationCode()
        );

        return $result;
    }
}
