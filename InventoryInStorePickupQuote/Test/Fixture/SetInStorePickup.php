<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupQuote\Test\Fixture;

use Magento\Framework\DataObject;
use Magento\InventoryInStorePickupApi\Model\GetPickupLocationInterface;
use Magento\InventoryInStorePickupQuote\Model\GetWebsiteCodeByStoreId;
use Magento\InventoryInStorePickupQuote\Model\ToQuoteAddress;
use Magento\InventoryInStorePickupShippingApi\Model\Carrier\InStorePickup;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartExtensionFactory;
use Magento\Quote\Model\ShippingAssignmentFactory;
use Magento\Quote\Model\ShippingFactory;
use Magento\TestFramework\Fixture\DataFixtureInterface;

class SetInStorePickup implements DataFixtureInterface
{
    /**
     * @var CartRepositoryInterface
     */
    private CartRepositoryInterface $cartRepository;

    /**
     * @var CartExtensionFactory
     */
    private CartExtensionFactory $cartExtensionFactory;

    /**
     * @var ShippingAssignmentFactory
     */
    private ShippingAssignmentFactory $shippingAssignmentFactory;

    /**
     * @var ShippingFactory
     */
    private ShippingFactory $shippingFactory;

    /**
     * @var GetPickupLocationInterface
     */
    private GetPickupLocationInterface $getPickupLocation;

    /**
     * @var ToQuoteAddress
     */
    private ToQuoteAddress $toQuoteAddress;

    /**
     * @var GetWebsiteCodeByStoreId
     */
    private GetWebsiteCodeByStoreId $getWebsiteCodeByStoreId;

    /**
     * @param CartRepositoryInterface $cartRepository
     * @param CartExtensionFactory $cartExtensionFactory
     * @param ShippingAssignmentFactory $shippingAssignmentFactory
     * @param ShippingFactory $shippingFactory
     * @param GetPickupLocationInterface $getPickupLocation
     * @param ToQuoteAddress $toQuoteAddress
     * @param GetWebsiteCodeByStoreId $getWebsiteCodeByStoreId
     */
    public function __construct(
        CartRepositoryInterface $cartRepository,
        CartExtensionFactory $cartExtensionFactory,
        ShippingAssignmentFactory $shippingAssignmentFactory,
        ShippingFactory $shippingFactory,
        GetPickupLocationInterface $getPickupLocation,
        ToQuoteAddress $toQuoteAddress,
        GetWebsiteCodeByStoreId $getWebsiteCodeByStoreId
    ) {
        $this->cartRepository = $cartRepository;
        $this->cartExtensionFactory = $cartExtensionFactory;
        $this->shippingAssignmentFactory = $shippingAssignmentFactory;
        $this->shippingFactory = $shippingFactory;
        $this->getPickupLocation = $getPickupLocation;
        $this->toQuoteAddress = $toQuoteAddress;
        $this->getWebsiteCodeByStoreId = $getWebsiteCodeByStoreId;
    }

    /**
     * {@inheritdoc}
     * @param array $data Parameters
     * <pre>
     *    $data = [
     *      'cart_id' => (int) Cart ID. Required
     *      'source_code' => (string) Source Code. Required
     *    ]
     * </pre>
     */
    public function apply(array $data = []): ?DataObject
    {
        $quote = $this->cartRepository->get($data['cart_id']);
        $pickupLocation = $this->getPickupLocation->execute(
            $data['source_code'],
            SalesChannelInterface::TYPE_WEBSITE,
            $this->getWebsiteCodeByStoreId->execute((int)$quote->getStoreId())
        );
        $address = $this->toQuoteAddress->convert($pickupLocation, $quote->getShippingAddress());
        $address->setFirstname($pickupLocation->getName());
        $address->setLastname('Store');
        $address->setTelephone($pickupLocation->getPhone());
        $cartExtension = $quote->getExtensionAttributes() ?? $this->cartExtensionFactory->create();

        $shippingAssignments = $cartExtension->getShippingAssignments() ?: [$this->shippingAssignmentFactory->create()];
        $shippingAssignment = $shippingAssignments[0];

        $shipping = $shippingAssignment->getShipping() ?? $this->shippingFactory->create();

        $shipping->setMethod(InStorePickup::DELIVERY_METHOD);
        $shipping->setAddress($address);
        $shippingAssignment->setShipping($shipping);
        $cartExtension->setShippingAssignments([$shippingAssignment]);
        $this->cartRepository->save($quote);

        return null;
    }
}
