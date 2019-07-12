<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupQuote\Model\Quote\ValidationRule;

use Magento\Framework\Validation\ValidationResultFactory;
use Magento\InventoryInStorePickupApi\Api\GetPickupLocationInterface;
use Magento\InventoryInStorePickupQuote\Model\GetWebsiteCodeByStoreId;
use Magento\InventoryInStorePickupQuote\Model\IsPickupLocationShippingAddress;
use Magento\InventoryInStorePickupQuote\Model\Quote\GetShipping;
use Magento\InventoryInStorePickupShippingApi\Model\Carrier\InStorePickup;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\ValidationRules\QuoteValidationRuleInterface;

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
     * @var IsPickupLocationShippingAddress
     */
    private $isPickupLocationShippingAddress;

    /**
     * @var GetPickupLocationInterface
     */
    private $getPickupLocation;

    /**
     * @var GetWebsiteCodeByStoreId
     */
    private $getWebsiteCodeByStoreId;

    /**
     * @var GetShipping
     */
    private $getShipping;

    /**
     * @param ValidationResultFactory $validationResultFactory
     * @param IsPickupLocationShippingAddress $isPickupLocationShippingAddress
     * @param GetPickupLocationInterface $getPickupLocation
     * @param GetWebsiteCodeByStoreId $getWebsiteCodeByStoreId
     * @param GetShipping $getShipping
     */
    public function __construct(
        ValidationResultFactory $validationResultFactory,
        IsPickupLocationShippingAddress $isPickupLocationShippingAddress,
        GetPickupLocationInterface $getPickupLocation,
        GetWebsiteCodeByStoreId $getWebsiteCodeByStoreId,
        GetShipping $getShipping
    ) {
        $this->validationResultFactory = $validationResultFactory;
        $this->isPickupLocationShippingAddress = $isPickupLocationShippingAddress;
        $this->getPickupLocation = $getPickupLocation;
        $this->getWebsiteCodeByStoreId = $getWebsiteCodeByStoreId;
        $this->getShipping = $getShipping;
    }

    /**
     * @inheritdoc
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function validate(Quote $quote): array
    {
        $validationErrors = [];

        $shipping = $this->getShipping->execute($quote);

        if ($shipping === null || $shipping->getMethod() !== InStorePickup::DELIVERY_METHOD) {
            return [$this->validationResultFactory->create(['errors' => $validationErrors])];
        }

        $address = $quote->getShippingAddress();

        $pickupLocation = $this->getPickupLocation->execute(
            $address->getExtensionAttributes()->getPickupLocationCode(),
            SalesChannelInterface::TYPE_WEBSITE,
            $this->getWebsiteCodeByStoreId->execute((int)$quote->getStoreId())
        );

        if (!$this->isPickupLocationShippingAddress->execute($pickupLocation, $address)) {
            $validationErrors[] = __(
                'Pickup Location Address does not match Shipping Address for In-Store Pickup Quote.'
            );
        }

        return [$this->validationResultFactory->create(['errors' => $validationErrors])];
    }
}
