<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupShipping\Model;

use Magento\InventoryInStorePickupShippingApi\Api\Data\ProductInfoInterface;
use Magento\InventoryInStorePickupShippingApi\Api\Data\RequestExtensionInterface;
use Magento\InventoryInStorePickupShippingApi\Api\Data\RequestInterface;

/**
 * @inheritdoc
 */
class Request implements RequestInterface
{
    /**
     * @var ProductInfoInterface[]
     */
    private $productsInfo;

    /**
     * @var RequestExtensionInterface|null
     */
    private $requestExtension;

    /**
     * @var string
     */
    private $scopeType;

    /**
     * @var string
     */
    private $scopeCode;

    /**
     * @param ProductInfoInterface[] $productsSku
     * @param string $scopeType
     * @param string $scopeCode
     * @param RequestExtensionInterface|null $requestExtension |null
     */
    public function __construct(
        array $productsInfo,
        string $scopeType,
        string $scopeCode,
        ?RequestExtensionInterface $requestExtension = null
    ) {
        $this->productsInfo = $productsInfo;
        $this->requestExtension = $requestExtension;
        $this->scopeType = $scopeType;
        $this->scopeCode = $scopeCode;
    }

    /**
     * @inheritdoc
     */
    public function getProductsInfo(): array
    {
        return $this->productsInfo;
    }

    /**
     * @inheritdoc
     */
    public function getExtensionAttributes(): ?RequestExtensionInterface
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

    /**
     * @inheritdoc
     */
    public function getScopeType(): string
    {
        return $this->scopeType;
    }

    /**
     * @inheritdoc
     *
     * @return string
     */
    public function getScopeCode(): string
    {
        return $this->scopeCode;
    }
}
