<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupShipping\Model\Carrier\Command;

use Magento\InventoryInStorePickupShippingApi\Api\Data\ShippingPriceRequestExtensionInterface;
use Magento\InventoryInStorePickupShippingApi\Api\Data\ShippingPriceRequestInterface;
use Magento\InventoryInStorePickupShippingApi\Api\Data\ShippingPriceRequestInterfaceFactory;
use Magento\InventoryInStorePickupShippingApi\Model\Carrier\Command\GetShippingPriceRequestInterface;
use Magento\Quote\Model\Quote\Address\RateRequest;

/**
 * @inheritdoc
 */
class GetShippingPriceRequest implements GetShippingPriceRequestInterface
{
    /**
     * @var ShippingPriceRequestInterfaceFactory
     */
    private $shippingPriceRequestFactory;

    /**
     * @param ShippingPriceRequestInterfaceFactory $shippingPriceRequestFactory
     */
    public function __construct(ShippingPriceRequestInterfaceFactory $shippingPriceRequestFactory)
    {
        $this->shippingPriceRequestFactory = $shippingPriceRequestFactory;
    }

    /**
     * @inheritdoc
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute(
        RateRequest $rateRequest,
        float $defaultPrice,
        float $freePackages,
        ?ShippingPriceRequestExtensionInterface $shippingPriceRequestExtension = null
    ): ShippingPriceRequestInterface {
        return $this->shippingPriceRequestFactory->create(
            [
                'defaultPrice' => $defaultPrice,
                'freePackages' => $freePackages,
                'shippingPriceRequestExtension' => $shippingPriceRequestExtension
            ]
        );
    }
}
