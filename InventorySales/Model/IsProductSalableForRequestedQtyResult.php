<?php
/**
 * Copyright 2020 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Model;

use Magento\InventorySalesApi\Api\Data\IsProductSalableForRequestedQtyResultExtensionInterface;
use Magento\InventorySalesApi\Api\Data\IsProductSalableForRequestedQtyResultInterface;
use Magento\InventorySalesApi\Api\Data\ProductSalabilityErrorInterface;

/**
 * @inheritDoc
 */
class IsProductSalableForRequestedQtyResult implements IsProductSalableForRequestedQtyResultInterface
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
     * @var array
     */
    private $errors;

    /**
     * @var IsProductSalableForRequestedQtyResultExtensionInterface|null
     */
    private $extensionAttributes;

    /**
     * @param string $sku
     * @param int $stockId
     * @param bool $isSalable
     * @param ProductSalabilityErrorInterface[] $errors
     * @param IsProductSalableForRequestedQtyResultExtensionInterface|null $extensionAttributes
     */
    public function __construct(
        string $sku,
        int $stockId,
        bool $isSalable,
        array $errors = [],
        ?IsProductSalableForRequestedQtyResultExtensionInterface $extensionAttributes = null
    ) {
        $this->sku = $sku;
        $this->isSalable = $isSalable;
        $this->extensionAttributes = $extensionAttributes;
        $this->errors = $errors;
        $this->stockId = $stockId;
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
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * @inheritDoc
     */
    public function setExtensionAttributes(IsProductSalableForRequestedQtyResultExtensionInterface $extAttributes): void
    {
        $this->extensionAttributes = $extAttributes;
    }

    /**
     * @inheritDoc
     */
    public function getExtensionAttributes(): ?IsProductSalableForRequestedQtyResultExtensionInterface
    {
        return $this->extensionAttributes;
    }
}
