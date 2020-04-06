<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Model;

use Magento\InventorySalesApi\Api\Data\IsProductSalableForRequestedQtyRequestInterface;
use Magento\InventorySalesApi\Api\Data\IsProductSalableForRequestedQtyRequestExtensionInterface;

/**
 * @inheritDoc
 */
class IsProductSalableForRequestedQtyRequest implements IsProductSalableForRequestedQtyRequestInterface
{
    /**
     * @var string
     */
    private $sku;

    /**
     * @var float
     */
    private $qty;

    /**
     * @var IsProductSalableForRequestedQtyRequestExtensionInterface|null
     */
    private $extensionAttributes;

    /**
     * @param string $sku
     * @param float $qty
     * @param IsProductSalableForRequestedQtyRequestExtensionInterface|null $extensionAttributes
     */
    public function __construct(string $sku, float $qty, $extensionAttributes = null)
    {
        $this->sku = $sku;
        $this->qty = $qty;
        $this->extensionAttributes = $extensionAttributes;
    }

    /**
     * @inheritDoc
     */
    public function getSku(): string
    {
        return $this->sku;
    }

    /**
     * @inheritDoc
     */
    public function getQty(): float
    {
        return $this->qty;
    }

    /**
     * @inheritDoc
     */
    public function setExtensionAttributes(IsProductSalableForRequestedQtyRequestExtensionInterface $attributes): void
    {
        $this->extensionAttributes = $attributes;
    }

    /**
     * @inheritDoc
     */
    public function getExtensionAttributes(): ?IsProductSalableForRequestedQtyRequestExtensionInterface
    {
        return $this->extensionAttributes;
    }
}
