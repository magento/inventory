<?php
/**
 * Copyright 2020 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Model;

use Magento\InventorySalesApi\Api\Data\IsProductSalableResultExtensionInterface;
use Magento\InventorySalesApi\Api\Data\IsProductSalableResultInterface;

/**
 * @inheritDoc
 */
class IsProductSalableResult implements IsProductSalableResultInterface
{
    /**
     * @var string
     */
    private $sku;

    /**
     * @var int
     */
    private $stockId;

    /**
     * @var bool
     */
    private $isSalable;

    /**
     * @var IsProductSalableResultExtensionInterface|null
     */
    private $extensionAttributes;

    /**
     * @param string $sku
     * @param int $stockId
     * @param bool $isSalable
     * @param IsProductSalableResultExtensionInterface|null $extensionAttributes
     */
    public function __construct(
        string $sku,
        int $stockId,
        bool $isSalable,
        ?IsProductSalableResultExtensionInterface $extensionAttributes = null
    ) {
        $this->sku = $sku;
        $this->stockId = $stockId;
        $this->isSalable = $isSalable;
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
    public function getStockId(): int
    {
        return $this->stockId;
    }

    /**
     * @inheritDoc
     */
    public function isSalable(): bool
    {
        return $this->isSalable;
    }

    /**
     * @inheritDoc
     */
    public function setExtensionAttributes(IsProductSalableResultExtensionInterface $extensionAttributes): void
    {
        $this->extensionAttributes = $extensionAttributes;
    }

    /**
     * @inheritDoc
     */
    public function getExtensionAttributes(): ?IsProductSalableResultExtensionInterface
    {
        return $this->extensionAttributes;
    }
}
