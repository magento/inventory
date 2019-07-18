<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupShipping\Model\Carrier;

use Magento\InventoryInStorePickupShippingApi\Api\Data\ShippingPriceRequestExtensionInterface;
use Magento\InventoryInStorePickupShippingApi\Api\Data\ShippingPriceRequestInterface;

/**
 * @inheritdoc
 * @codeCoverageIgnore
 */
class ShippingPriceRequest implements ShippingPriceRequestInterface
{
    /**
     * @var float
     */
    private $defaultPrice;

    /**
     * @var float
     */
    private $freePackages;

    /**
     * @var ShippingPriceRequestExtensionInterface
     */
    private $shippingPriceRequestExtension;

    /**
     * @param float $defaultPrice
     * @param float $freePackages
     * @param ShippingPriceRequestExtensionInterface|null $shippingPriceRequestExtension
     */
    public function __construct(
        float $defaultPrice,
        float $freePackages,
        ?ShippingPriceRequestExtensionInterface $shippingPriceRequestExtension = null
    ) {
        $this->defaultPrice = $defaultPrice;
        $this->freePackages = $freePackages;
        $this->shippingPriceRequestExtension = $shippingPriceRequestExtension;
    }

    /**
     * @inheritdoc
     */
    public function getDefaultPrice(): float
    {
        return $this->defaultPrice;
    }

    /**
     * @inheritdoc
     */
    public function getFreePackages(): float
    {
        return $this->freePackages;
    }

    /**
     * @inheritdoc
     */
    public function setExtensionAttributes(?ShippingPriceRequestExtensionInterface $extensionAttributes): void
    {
        $this->shippingPriceRequestExtension = $extensionAttributes;
    }

    /**
     * @inheritdoc
     */
    public function getExtensionAttributes(): ?ShippingPriceRequestExtensionInterface
    {
        return $this->shippingPriceRequestExtension;
    }
}
