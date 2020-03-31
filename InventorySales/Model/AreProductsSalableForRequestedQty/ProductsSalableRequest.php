<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Model\AreProductsSalableForRequestedQty;

use Magento\InventorySalesApi\Api\Data\SkuQtyRequestExtensionInterface;
use Magento\InventorySalesApi\Api\Data\SkuQtyRequestInterface;

/**
 * @inheritDoc
 */
class ProductsSalableRequest implements SkuQtyRequestInterface
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
     * @var SkuQtyRequestExtensionInterface|null
     */
    private $extensionAttributes;

    /**
     * @param string $sku
     * @param float $qty
     * @param SkuQtyRequestExtensionInterface|null $extensionAttributes
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
    public function setExtensionAttributes(
        SkuQtyRequestExtensionInterface $extensionAttributes
    ): void {
        $this->extensionAttributes = $extensionAttributes;
    }

    /**
     * @inheritDoc
     */
    public function getExtensionAttributes(): ?SkuQtyRequestExtensionInterface
    {
        return $this->extensionAttributes;
    }
}
