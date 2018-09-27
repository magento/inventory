<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryShipping\Model;

use Magento\InventorySourceSelectionApi\Api\Data\AddressRequestInterface;
use Magento\InventorySourceSelectionApi\Api\Data\AddressRequestInterfaceFactory;
use Magento\Sales\Api\Data\OrderInterface;

/**
 * Create an address request from an order
 */
class GetAddressRequestFromOrder
{
    /**
     * @var AddressRequestInterfaceFactory
     */
    private $addressRequestInterfaceFactory;

    /**
     * GetAddressRequestFromOrder constructor
     *
     * @param AddressRequestInterfaceFactory $addressRequestInterfaceFactory
     * @SuppressWarnings(PHPMD.LongVariable)
     */
    public function __construct(
        AddressRequestInterfaceFactory $addressRequestInterfaceFactory
    ) {
        $this->addressRequestInterfaceFactory = $addressRequestInterfaceFactory;
    }

    /**
     * Create an address request from an order
     *
     * @param OrderInterface $order
     * @return AddressRequestInterface
     */
    public function execute(OrderInterface $order): AddressRequestInterface
    {
        // TODO: Should we refactor or add getShippingAddress in the interface?

        /** @var \Magento\Sales\Model\Order\Address $shippingAddress */
        $shippingAddress = $order->getShippingAddress();

        return $this->addressRequestInterfaceFactory->create([
            'country' => $shippingAddress->getCountryId(),
            'postcode' => $shippingAddress->getPostcode(),
            'streetAddress' => implode("\n", $shippingAddress->getStreet()),
            'region' => $shippingAddress->getRegion() ?? $shippingAddress->getRegionCode() ?? '',
            'city' => $shippingAddress->getCity()
        ]);
    }
}
