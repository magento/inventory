<?php
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventorySourceSelectionApi\Api\Data;

/**
 * Result of how we will deduct product qty from different Sources
 *
 * @api
 */
interface SourceSelectionResultInterface extends \Magento\Framework\Api\ExtensibleDataInterface
{
    /**
     * Gets the list of source selection items representing how product quantities are deducted from sources.
     *
     * @return \Magento\InventorySourceSelectionApi\Api\Data\SourceSelectionItemInterface[]
     */
    public function getSourceSelectionItems(): array;

    /**
     * Checks if the source selection result is eligible for shipping.
     *
     * @return bool
     */
    public function isShippable(): bool;

    /**
     * Retrieve existing extension attributes object
     *
     * @return \Magento\InventorySourceSelectionApi\Api\Data\SourceSelectionResultExtensionInterface|null
     */
    public function getExtensionAttributes(): ?SourceSelectionResultExtensionInterface;

    /**
     * Set an extension attributes object
     *
     * @param \Magento\InventorySourceSelectionApi\Api\Data\SourceSelectionResultExtensionInterface $extensionAttributes
     * @return void
     */
    public function setExtensionAttributes(
        \Magento\InventorySourceSelectionApi\Api\Data\SourceSelectionResultExtensionInterface $extensionAttributes
    ): void;
}
