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
use Magento\InventoryInStorePickupShippingApi\Model\IsInStorePickupDeliveryCartInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\ValidationRules\QuoteValidationRuleInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\InventoryInStorePickupApi\Api\Data\PickupLocationInterface;
use Magento\Framework\Exception\NoSuchEntityException;

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
     * @var IsInStorePickupDeliveryCartInterface
     */
    private $isInStorePickupDeliveryCart;

    /**
     * @param ValidationResultFactory $validationResultFactory
     * @param IsPickupLocationShippingAddress $isPickupLocationShippingAddress
     * @param GetPickupLocationInterface $getPickupLocation
     * @param GetWebsiteCodeByStoreId $getWebsiteCodeByStoreId
     * @param IsInStorePickupDeliveryCartInterface $isInStorePickupDeliveryCart
     */
    public function __construct(
        ValidationResultFactory $validationResultFactory,
        IsPickupLocationShippingAddress $isPickupLocationShippingAddress,
        GetPickupLocationInterface $getPickupLocation,
        GetWebsiteCodeByStoreId $getWebsiteCodeByStoreId,
        IsInStorePickupDeliveryCartInterface $isInStorePickupDeliveryCart
    ) {
        $this->validationResultFactory = $validationResultFactory;
        $this->isPickupLocationShippingAddress = $isPickupLocationShippingAddress;
        $this->getPickupLocation = $getPickupLocation;
        $this->getWebsiteCodeByStoreId = $getWebsiteCodeByStoreId;
        $this->isInStorePickupDeliveryCart = $isInStorePickupDeliveryCart;
    }

    /**
     * @inheritdoc
     * @throws NoSuchEntityException
     */
    public function validate(Quote $quote): array
    {
        $validationErrors = [];

        if (!$this->isInStorePickupDeliveryCart->execute($quote)) {
            return [$this->validationResultFactory->create(['errors' => $validationErrors])];
        }

        $address = $quote->getShippingAddress();

        $pickupLocation = $this->getPickupLocation($quote, $address);

        if (!$this->isPickupLocationShippingAddress->execute($pickupLocation, $address)) {
            $validationErrors[] = __(
                'Pickup Location Address does not match Shipping Address for In-Store Pickup Quote.'
            );
        }

        return [$this->validationResultFactory->create(['errors' => $validationErrors])];
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
}
