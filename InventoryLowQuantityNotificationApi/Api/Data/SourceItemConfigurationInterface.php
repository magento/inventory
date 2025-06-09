<?php
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryLowQuantityNotificationApi\Api\Data;

/**
 * Represents a Source Item Configuration object
 *
 * Used fully qualified namespaces in annotations for proper work of WebApi request parser
 * phpcs:disable Generic.Files.LineLength.TooLong
 *
 * @api
 */
interface SourceItemConfigurationInterface extends \Magento\Framework\Api\ExtensibleDataInterface
{
    /**
     * Constant for fields in data array
     */
    public const SOURCE_CODE = 'source_code';
    public const SKU = 'sku';
    public const INVENTORY_NOTIFY_QTY = 'notify_stock_qty';

    /**
     * Get source code
     *
     * @return string|null
     */
    public function getSourceCode(): ?string;

    /**
     * Set source code
     *
     * @param string $sourceCode
     * @return void
     */
    public function setSourceCode(string $sourceCode): void;

    /**
     * Get notify stock qty
     *
     * @return float|null
     */
    public function getNotifyStockQty(): ?float;

    /**
     * Set notify stock qty
     *
     * @param float|null $quantity
     * @return void
     */
    public function setNotifyStockQty(?float $quantity): void;

    /**
     * Get SKU
     *
     * @return string|null
     */
    public function getSku(): ?string;

    /**
     * Set SKU
     *
     * @param string $sku
     * @return void
     */
    public function setSku(string $sku): void;

    /**
     * Retrieve existing extension attributes object
     *
     * @return \Magento\InventoryLowQuantityNotificationApi\Api\Data\SourceItemConfigurationExtensionInterface|null
     */
    public function getExtensionAttributes(): ?SourceItemConfigurationExtensionInterface;

    /**
     * Set an extension attributes object
     *
     * @param \Magento\InventoryLowQuantityNotificationApi\Api\Data\SourceItemConfigurationExtensionInterface $extensionAttributes
     * @return void
     */
    public function setExtensionAttributes(SourceItemConfigurationExtensionInterface $extensionAttributes): void;
}
