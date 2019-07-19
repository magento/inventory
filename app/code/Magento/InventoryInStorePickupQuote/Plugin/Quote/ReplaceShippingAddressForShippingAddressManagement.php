<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupQuote\Plugin\Quote;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\StateException;
use Magento\InventoryInStorePickupApi\Api\Data\PickupLocationInterface;
use Magento\InventoryInStorePickupApi\Api\GetPickupLocationInterface;
use Magento\InventoryInStorePickupQuote\Model\GetWebsiteCodeByStoreId;
use Magento\InventoryInStorePickupQuote\Model\IsPickupLocationShippingAddress;
use Magento\InventoryInStorePickupQuote\Model\ToQuoteAddress;
use Magento\InventoryInStorePickupShippingApi\Model\IsInStorePickupDeliveryCartInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\ShippingAddressManagementInterface;

/**
 * Replace Shipping Address with Pickup Location Shipping Address for Shipping Address Management service.
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ReplaceShippingAddressForShippingAddressManagement
{
    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

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
     * @var GetWebsiteCodeByStoreId
     */
    private $getWebsiteCodeByStoreId;

    /**
     * @var IsInStorePickupDeliveryCartInterface
     */
    private $isInStorePickupDeliveryCart;

    /**
     * @param CartRepositoryInterface $cartRepository
     * @param GetPickupLocationInterface $getPickupLocation
     * @param IsPickupLocationShippingAddress $isPickupLocationShippingAddress
     * @param ToQuoteAddress $addressConverter
     * @param IsInStorePickupDeliveryCartInterface $isInStorePickupDeliveryCart
     * @param GetWebsiteCodeByStoreId $getWebsiteCodeByStoreId
     */
    public function __construct(
        CartRepositoryInterface $cartRepository,
        GetPickupLocationInterface $getPickupLocation,
        IsPickupLocationShippingAddress $isPickupLocationShippingAddress,
        ToQuoteAddress $addressConverter,
        IsInStorePickupDeliveryCartInterface $isInStorePickupDeliveryCart,
        GetWebsiteCodeByStoreId $getWebsiteCodeByStoreId
    ) {
        $this->cartRepository = $cartRepository;
        $this->getPickupLocation = $getPickupLocation;
        $this->isPickupLocationShippingAddress = $isPickupLocationShippingAddress;
        $this->addressConverter = $addressConverter;
        $this->getWebsiteCodeByStoreId = $getWebsiteCodeByStoreId;
        $this->isInStorePickupDeliveryCart = $isInStorePickupDeliveryCart;
    }

    /**
     * Check and replace Quote Address with Pickup Location address for In-Store Pickup Quote.
     *
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

        if (!$this->isInStorePickupDeliveryCart->execute($quote)) {
            return [$cartId, $address];
        }

        $this->validateQuoteAddressPickupLocation($address);
        $pickupLocation = $this->getPickupLocation($quote, $address);

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

    /**
     * Get Pickup Location entity, assigned to Shipping Address.
     *
     * @param CartInterface $quote
     * @param AddressInterface $address
     *
     * @return PickupLocationInterface
     * @throws NoSuchEntityException
     */
    private function getPickupLocation(CartInterface $quote, AddressInterface $address): PickupLocationInterface
    {
        return $this->getPickupLocation->execute(
            $address->getExtensionAttributes()->getPickupLocationCode(),
            SalesChannelInterface::TYPE_WEBSITE,
            $this->getWebsiteCodeByStoreId->execute((int)$quote->getStoreId())
        );
    }

    /**
     * Validate if Quote Shipping Address has assigned Pickup Location.
     *
     * @param AddressInterface $address
     *
     * @return void
     * @throws StateException
     */
    private function validateQuoteAddressPickupLocation(AddressInterface $address): void
    {
        //TODO set Same As Billing button should be removed or validation will fail in admin
        if (!$address->getExtensionAttributes() || !$address->getExtensionAttributes()->getPickupLocationCode()) {
            throw new StateException(__('Pickup Location Code is required for In-Store Pickup Delivery Method.'));
        }
    }
}
