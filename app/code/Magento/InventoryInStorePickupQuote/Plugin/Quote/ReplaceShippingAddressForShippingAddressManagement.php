<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupQuote\Plugin\Quote;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryInStorePickupApi\Api\Data\PickupLocationInterface;
use Magento\InventoryInStorePickupApi\Model\GetPickupLocationInterface;
use Magento\InventoryInStorePickupQuote\Model\GetWebsiteCodeByStoreId;
use Magento\InventoryInStorePickupQuote\Model\IsPickupLocationShippingAddress;
use Magento\InventoryInStorePickupQuote\Model\ToQuoteAddress;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\ShippingAddressManagementInterface;

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
     * @param CartRepositoryInterface $cartRepository
     * @param GetPickupLocationInterface $getPickupLocation
     * @param IsPickupLocationShippingAddress $isPickupLocationShippingAddress
     * @param ToQuoteAddress $addressConverter
     * @param GetWebsiteCodeByStoreId $getWebsiteCodeByStoreId
     */
    public function __construct(
        CartRepositoryInterface $cartRepository,
        GetPickupLocationInterface $getPickupLocation,
        IsPickupLocationShippingAddress $isPickupLocationShippingAddress,
        ToQuoteAddress $addressConverter,
        GetWebsiteCodeByStoreId $getWebsiteCodeByStoreId
    ) {
        $this->cartRepository = $cartRepository;
        $this->getPickupLocation = $getPickupLocation;
        $this->isPickupLocationShippingAddress = $isPickupLocationShippingAddress;
        $this->addressConverter = $addressConverter;
        $this->getWebsiteCodeByStoreId = $getWebsiteCodeByStoreId;
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
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeAssign(
        ShippingAddressManagementInterface $subject,
        int $cartId,
        AddressInterface $address
    ): array {
        $quote = $this->cartRepository->getActive($cartId);

        if (!$this->isQuoteAddressHasPickupLocationCode($address)) {
            return [$cartId, $address];
        }

        $pickupLocation = $this->getPickupLocation($quote, $address);

        if ($this->isPickupLocationShippingAddress->execute($pickupLocation, $address)) {
            return [$cartId, $address];
        }

        $address = $this->addressConverter->convert($pickupLocation, $address);

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
     * Check if Quote Shipping Address has assigned Pickup Location.
     *
     * @param AddressInterface $address
     *
     * @return bool
     */
    private function isQuoteAddressHasPickupLocationCode(AddressInterface $address): bool
    {
        return $address->getExtensionAttributes() && $address->getExtensionAttributes()->getPickupLocationCode();
    }
}
