<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model;

use Magento\InventoryInStorePickupApi\Api\Data\SearchRequest\ProductInfoExtensionInterface;
use Magento\InventoryInStorePickupApi\Api\Data\SearchRequest\ProductInfoInterface;

/**
 * @inheritdoc
 */
class ProductInfo implements ProductInfoInterface
{
    /**
     * @var string
     */
    private $sku;

    /**
     * @var ProductInfoExtensionInterface
     */
    private $productInfoExtension;

    /**
     * @param string $sku
     * @param ProductInfoExtensionInterface|null $productInfoExtension
     */
    public function __construct(string $sku, ?ProductInfoExtensionInterface $productInfoExtension = null)
    {
        $this->sku = $sku;
        $this->productInfoExtension = $productInfoExtension;
    }

    /**
     * @inheritdoc
     */
    public function getSku(): string
    {
        return $this->sku;
    }

    /**
     * @inheritdoc
     */
    public function getExtensionAttributes(): ?ProductInfoExtensionInterface
    {
        return $this->productInfoExtension;
    }

    /**
     * @inheritdoc
     */
    public function setExtensionAttributes(ProductInfoExtensionInterface $extension): void
    {
        $this->productInfoExtension = $extension;
    }
}
