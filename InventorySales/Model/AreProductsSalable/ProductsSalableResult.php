<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Model\AreProductsSalable;

use Magento\InventorySalesApi\Api\Data\AreProductsSalableResultExtensionInterface;
use Magento\InventorySalesApi\Api\Data\AreProductsSalableResultInterface;
use Magento\InventorySalesApi\Api\Data\IsProductSalableResultInterface;

/**
 * @inheritDoc
 */
class ProductsSalableResult implements AreProductsSalableResultInterface
{
    /**
     * @var array
     */
    private $results;

    /**
     * @var AreProductsSalableResultExtensionInterface|null
     */
    private $extensionAttributes;

    /**
     * @param IsProductSalableResultInterface[] $results
     * @param AreProductsSalableResultExtensionInterface|null $extensionAttributes
     */
    public function __construct(
        array $results = [],
        AreProductsSalableResultExtensionInterface $extensionAttributes = null
    ) {
        $this->results = $results;
        $this->extensionAttributes = $extensionAttributes;
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
    public function setExtensionAttributes(AreProductsSalableResultExtensionInterface $extensionAttributes): void
    {
        $this->extensionAttributes = $extensionAttributes;
    }

    /**
     * @inheritDoc
     */
    public function getExtensionAttributes(): ?AreProductsSalableResultExtensionInterface
    {
        return $this->extensionAttributes;
    }
}
