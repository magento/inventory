<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Model\AreProductsSalableForRequestedQty;

use Magento\InventorySalesApi\Api\Data\ProductSalableForRequestedQtyInfoExtensionInterface;
use Magento\InventorySalesApi\Api\Data\ProductSalableForRequestedQtyInfoInterface;

/**
 * @inheritDoc
 */
class ProductsSalableInfo implements ProductSalableForRequestedQtyInfoInterface
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
     * @var ProductSalableForRequestedQtyInfoExtensionInterface|null
     */
    private $extensionAttributes;

    /**
     * @param string $sku
     * @param float $qty
     * @param ProductSalableForRequestedQtyInfoExtensionInterface|null $extensionAttributes
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
        ProductSalableForRequestedQtyInfoExtensionInterface $extAttributes
    ): void {
        $this->extensionAttributes = $extAttributes;
    }

    /**
     * @inheritDoc
     */
    public function getExtensionAttributes(): ?ProductSalableForRequestedQtyInfoExtensionInterface
    {
        return $this->extensionAttributes;
    }
}
