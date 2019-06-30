<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupQuote\Plugin\Checkout;

use Magento\Checkout\Api\Data\ShippingInformationInterface;
use Magento\Checkout\Api\ShippingInformationManagementInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\StateException;
use Magento\InventoryInStorePickupApi\Api\GetPickupLocationInterface;
use Magento\InventoryInStorePickupQuote\Model\IsPickupLocationShippingAddress;
use Magento\InventoryInStorePickupQuote\Model\ToQuoteAddress;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Store\Api\WebsiteRepositoryInterface;

/**
 * Assign Pickup Location Shipping Address for In-Store Pickup Quote.
 */
class AddPickupLocationAddressToShippingInformation
{
    /**
     * @var GetPickupLocationInterface
     */
    private $getPickupLocation;

    /**
     * @var ToQuoteAddress
     */
    private $addressConverter;

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
     * @var IsPickupLocationShippingAddress
     */
    private $isPickupLocationShippingAddress;

    /**
     * @param GetPickupLocationInterface $getPickupLocation
     * @param ToQuoteAddress $addressConverter
     * @param CartRepositoryInterface $cartRepository
     * @param StoreRepositoryInterface $storeRepository
     * @param WebsiteRepositoryInterface $websiteRepository
     * @param IsPickupLocationShippingAddress $isPickupLocationShippingAddress
     */
    public function __construct(
        GetPickupLocationInterface $getPickupLocation,
        ToQuoteAddress $addressConverter,
        CartRepositoryInterface $cartRepository,
        StoreRepositoryInterface $storeRepository,
        WebsiteRepositoryInterface $websiteRepository,
        IsPickupLocationShippingAddress $isPickupLocationShippingAddress
    ) {
        $this->getPickupLocation = $getPickupLocation;
        $this->addressConverter = $addressConverter;
        $this->cartRepository = $cartRepository;
        $this->storeRepository = $storeRepository;
        $this->websiteRepository = $websiteRepository;
        $this->isPickupLocationShippingAddress = $isPickupLocationShippingAddress;
    }

    /**
     * Repalce Shipping Address with Pickup Location address for In-Store Pickup Delovery
     *
     * @param ShippingInformationManagementInterface $subject
     * @param int $cartId
     * @param ShippingInformationInterface $addressInformation
     *
     * @return array
     * @throws NoSuchEntityException
     * @throws StateException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeSaveAddressInformation(
        ShippingInformationManagementInterface $subject,
        int $cartId,
        ShippingInformationInterface $addressInformation
    ) {
        $shippingCarrierCode = $addressInformation->getShippingCarrierCode();
        $shippingMethodCode = $addressInformation->getShippingMethodCode();
        if ($shippingCarrierCode != 'in_store' && $shippingMethodCode != 'pickup') {
            return [$cartId, $addressInformation];
        }

        $shippingAddress = $addressInformation->getShippingAddress();

        if (!$shippingAddress->getExtensionAttributes() ||
            !$shippingAddress->getExtensionAttributes()->getPickupLocationCode()
        ) {
            throw new StateException(__('Pickup Location Code is required for In-Store Pickup Delivery Method.'));
        }

        $quote = $this->cartRepository->getActive($cartId);
        $store = $this->storeRepository->getById($quote->getStoreId());
        $website = $this->websiteRepository->getById($store->getWebsiteId());

        $pickupLocation = $this->getPickupLocation->execute(
            $shippingAddress->getExtensionAttributes()->getPickupLocationCode(),
            SalesChannelInterface::TYPE_WEBSITE,
            $website->getCode()
        );

        if ($this->isPickupLocationShippingAddress->execute($pickupLocation, $shippingAddress)) {
            return [$cartId, $addressInformation];
        }

        /**
         * @TODO Refactor when issue will be resolved in core.
         * @see Please check issue in core for more details: https://github.com/magento/magento2/issues/23386.
         */
        $shippingAddress = $this->addressConverter->convert(
            $pickupLocation,
            $shippingAddress,
            ['extension_attribute_pickup_location_code_pickup_location_code' => $pickupLocation->getSourceCode()]
        );
        $addressInformation->setShippingAddress($shippingAddress);

        return [$cartId, $addressInformation];
    }
}
