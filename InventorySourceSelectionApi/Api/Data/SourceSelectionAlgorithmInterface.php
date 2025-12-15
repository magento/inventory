<?php
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventorySourceSelectionApi\Api\Data;

/**
 * Data Interface representing particular Source Selection Algorithm
 *
 * @api
 */
interface SourceSelectionAlgorithmInterface extends \Magento\Framework\Api\ExtensibleDataInterface
{
    /**
     * Gets the code of the Source Selection Algorithm as a string.
     *
     * @return string
     */
    public function getCode(): string;

    /**
     * Gets the title of the Source Selection Algorithm as a string.
     *
     * @return string
     */
    public function getTitle(): string;

    /**
     * Gets the description of the Source Selection Algorithm as a string.
     *
     * @return string
     */
    public function getDescription(): string;

    /**
     * Retrieve existing extension attributes object
     *
     * @return \Magento\InventorySourceSelectionApi\Api\Data\SourceSelectionAlgorithmExtensionInterface|null
     */
    public function getExtensionAttributes(): ?SourceSelectionAlgorithmExtensionInterface;

    /**
     * Set an extension attributes object
     *
     * @codingStandardsIgnoreStart
     * @param \Magento\InventorySourceSelectionApi\Api\Data\SourceSelectionAlgorithmExtensionInterface $extensionAttributes
     * @codingStandardsIgnoreEnd
     * @return void
     */
    public function setExtensionAttributes(
        \Magento\InventorySourceSelectionApi\Api\Data\SourceSelectionAlgorithmExtensionInterface $extensionAttributes
    ): void;
}
