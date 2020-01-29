<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Model\AreProductsSalableForRequestedQty;

use Magento\InventorySalesApi\Api\Data\ProductsSalableResultExtensionInterface;
use Magento\InventorySalesApi\Api\Data\ProductsSalableResultInterface;

/**
 * @inheritDoc
 */
class ProductsSalableResult implements ProductsSalableResultInterface
{
    /**
     * @var array
     */
    private $results;

    /**
     * @var ProductsSalableResultExtensionInterface|null
     */
    private $extensionAttributes;

    /**
     * @param array $results
     */
    public function __construct(array $results = [])
    {
        $this->results = $results;
    }

    /**
     * @inheritDoc
     */
    public function getSalable(): array
    {
        return $this->results;
    }

    /**
     * @inheritDoc
     */
    public function getExtensionAttributes(): ?ProductsSalableResultExtensionInterface
    {
        return $this->extensionAttributes;
    }

    /**
     * @inheritDoc
     */
    public function setExtensionAttributes(ProductsSalableResultExtensionInterface $extensionAttributes): void
    {
        $this->extensionAttributes = $extensionAttributes;
    }
}
