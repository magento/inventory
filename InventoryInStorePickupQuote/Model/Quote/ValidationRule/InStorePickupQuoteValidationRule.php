<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupQuote\Model\Quote\ValidationRule;

use Magento\Framework\Validation\ValidationResultFactory;
use Magento\InventoryInStorePickupApi\Api\GetPickupLocationInterface;
use Magento\InventoryInStorePickupQuote\Model\IsPickupLocationShippingAddress;
use Magento\InventoryInStorePickupShippingApi\Model\Carrier\InStorePickup;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\ValidationRules\QuoteValidationRuleInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Store\Api\WebsiteRepositoryInterface;

/**
 * Validate Quote for In-Store Pickup Delivery Method.
 */
class InStorePickupQuoteValidationRule implements QuoteValidationRuleInterface
{
    /**
     * @var ValidationResultFactory
     */
    private $validationResultFactory;

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
     * @var GetPickupLocationInterface
     */
    private $getPickupLocation;

    /**
     * InStorePickupQuoteValidationRule constructor.
     *
     * @param ValidationResultFactory $validationResultFactory
     * @param StoreRepositoryInterface $storeRepository
     * @param WebsiteRepositoryInterface $websiteRepository
     * @param IsPickupLocationShippingAddress $isPickupLocationShippingAddress
     * @param GetPickupLocationInterface $getPickupLocation
     */
    public function __construct(
        ValidationResultFactory $validationResultFactory,
        StoreRepositoryInterface $storeRepository,
        WebsiteRepositoryInterface $websiteRepository,
        IsPickupLocationShippingAddress $isPickupLocationShippingAddress,
        GetPickupLocationInterface $getPickupLocation
    ) {
        $this->validationResultFactory = $validationResultFactory;
        $this->storeRepository = $storeRepository;
        $this->websiteRepository = $websiteRepository;
        $this->isPickupLocationShippingAddress = $isPickupLocationShippingAddress;
        $this->getPickupLocation = $getPickupLocation;
    }

    /**
     * @inheritdoc
     */
    public function validate(Quote $quote): array
    {
        $validationErrors = [];

        if (!$quote->getExtensionAttributes() || !$quote->getExtensionAttributes()->getShippingAssignments()) {
            return [$this->validationResultFactory->create(['errors' => $validationErrors])];
        }

        $shippingAssignments = $quote->getExtensionAttributes()->getShippingAssignments();
        /** @var ShippingAssignmentInterface $shippingAssignment */
        $shippingAssignment = current($shippingAssignments);
        $shipping = $shippingAssignment->getShipping();

        if ($shipping->getMethod() !== InStorePickup::DELIVERY_METHOD) {
            return [$this->validationResultFactory->create(['errors' => $validationErrors])];
        }

        $store = $this->storeRepository->getById($quote->getStoreId());
        $website = $this->websiteRepository->getById($store->getWebsiteId());

        $address = $quote->getShippingAddress();

        $pickupLocation = $this->getPickupLocation->execute(
            $address->getExtensionAttributes()->getPickupLocationCode(),
            SalesChannelInterface::TYPE_WEBSITE,
            $website->getCode()
        );

        if (!$this->isPickupLocationShippingAddress->execute($pickupLocation, $address)) {
            $validationErrors[] = __(
                'Pickup Location Address does not match Shipping Address for In-Store Pickup Quote.'
            );
        }

        return [$this->validationResultFactory->create(['errors' => $validationErrors])];
    }
}
