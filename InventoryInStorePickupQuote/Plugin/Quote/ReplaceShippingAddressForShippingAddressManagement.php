<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupQuote\Plugin\Quote;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\StateException;
use Magento\InventoryInStorePickupApi\Api\GetPickupLocationInterface;
use Magento\InventoryInStorePickupQuote\Model\IsPickupLocationShippingAddress;
use Magento\InventoryInStorePickupQuote\Model\ToQuoteAddress;
use Magento\InventoryInStorePickupShippingApi\Model\Carrier\InStorePickup;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Model\ShippingAddressManagementInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Store\Api\WebsiteRepositoryInterface;

/**
 * Replace Shipping Address with Pickup Location Shipping Address for Shipping Address Management service.
 */
class ReplaceShippingAddressForShippingAddressManagement
{
    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * @var StoreRepositoryInterface
     */
    private $storeRepository;

    /**
     * @var WebsiteRepositoryInterface
     */
    private $websiteRepository;

    /**
     * @var GetPickupLocationInterface
     */
    private $getPickupLocation;

    /**
     * @var IsPickupLocationShippingAddress
     */
    private $isPickupLocationShippingAddress;

    /**
     * @var ToQuoteAddress
     */
    private $addressConverter;

    /**
     * @param CartRepositoryInterface $cartRepository
     * @param StoreRepositoryInterface $storeRepository
     * @param WebsiteRepositoryInterface $websiteRepository
     * @param GetPickupLocationInterface $getPickupLocation
     * @param IsPickupLocationShippingAddress $isPickupLocationShippingAddress
     * @param ToQuoteAddress $addressConverter
     */
    public function __construct(
        CartRepositoryInterface $cartRepository,
        StoreRepositoryInterface $storeRepository,
        WebsiteRepositoryInterface $websiteRepository,
        GetPickupLocationInterface $getPickupLocation,
        IsPickupLocationShippingAddress $isPickupLocationShippingAddress,
        ToQuoteAddress $addressConverter
    ) {
        $this->cartRepository = $cartRepository;
        $this->storeRepository = $storeRepository;
        $this->websiteRepository = $websiteRepository;
        $this->getPickupLocation = $getPickupLocation;
        $this->isPickupLocationShippingAddress = $isPickupLocationShippingAddress;
        $this->addressConverter = $addressConverter;
    }

    /**
     * @param ShippingAddressManagementInterface $subject
     * @param int $cartId
     * @param AddressInterface $address
     *
     * @return array
     * @throws NoSuchEntityException
     * @throws StateException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeAssign(
        ShippingAddressManagementInterface $subject,
        int $cartId,
        AddressInterface $address
    ): array {
        $quote = $this->cartRepository->getActive($cartId);

        if (!$quote->getExtensionAttributes() || !$quote->getExtensionAttributes()->getShippingAssignments()) {
            return [$cartId, $address];
        }

        $shippingAssignments = $quote->getExtensionAttributes()->getShippingAssignments();
        /** @var ShippingAssignmentInterface $shippingAssignment */
        $shippingAssignment = current($shippingAssignments);
        $shipping = $shippingAssignment->getShipping();

        if ($shipping->getMethod() !== InStorePickup::DELIVERY_METHOD) {
            return [$cartId, $address];
        }

        if (!$address->getExtensionAttributes() || !$address->getExtensionAttributes()->getPickupLocationCode()) {
            throw new StateException(__('Pickup Location Code is required for In-Store Pickup Delivery Method.'));
        }

        $store = $this->storeRepository->getById($quote->getStoreId());
        $website = $this->websiteRepository->getById($store->getWebsiteId());

        $pickupLocation = $this->getPickupLocation->execute(
            $address->getExtensionAttributes()->getPickupLocationCode(),
            SalesChannelInterface::TYPE_WEBSITE,
            $website->getCode()
        );

        if ($this->isPickupLocationShippingAddress->execute($pickupLocation, $address)) {
            return [$cartId, $address];
        }

        /**
         * @TODO Refactor when issue will be resolved in core.
         * @see Please check issue in core for more details: https://github.com/magento/magento2/issues/23386.
         */
        $address = $this->addressConverter->convert(
            $pickupLocation,
            $address,
            ['extension_attribute_pickup_location_code_pickup_location_code' => $pickupLocation->getSourceCode()]
            );

        return [$cartId, $address];
    }
}
