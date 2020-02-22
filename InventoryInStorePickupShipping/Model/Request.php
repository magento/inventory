<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupShipping\Model;

use Magento\InventoryInStorePickupShippingApi\Api\Data\RequestExtensionInterface;
use Magento\InventoryInStorePickupShippingApi\Api\Data\RequestInterface;

/**
 * @inheritdoc
 */
class Request implements RequestInterface
{
    /**
     * @var string[]
     */
    private $productsSku;

    /**
     * @var RequestExtensionInterface|null
     */
    private $requestExtension;

    /**
     * @param array $productsSku
     * @param RequestExtensionInterface|null $requestExtension
     */
    public function __construct(
        array $productsSku,
        ?RequestExtensionInterface $requestExtension
    ) {
        $this->productsSku = $productsSku;
        $this->requestExtension = $requestExtension;
    }

    /**
     * @inheritdoc
     */
    public function getProductsSku(): array
    {
        return $this->productsSku;
    }

    /**
     * @inheritdoc
     */
    public function getExtensionAttributes(): RequestExtensionInterface
    {
        return $this->requestExtension;
    }

    /**
     * @inheritdoc
     */
    public function setExtensionAttributes(RequestExtensionInterface $requestExtension): void
    {
        $this->requestExtension = $requestExtension;
    }
}
