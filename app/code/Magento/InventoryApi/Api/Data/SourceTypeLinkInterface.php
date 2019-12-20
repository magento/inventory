<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryApi\Api\Data;

use Magento\Framework\Api\ExtensibleDataInterface;

/**
 * Represents relation between some source and type of source
 *
 * Used fully qualified namespaces in annotations for proper work of WebApi request parser
 *
 */
interface SourceTypeLinkInterface extends ExtensibleDataInterface
{
    /**#@+
     * Constants for keys of data array. Identical to the name of the getter in snake case
     */
    const TYPE_CODE = 'type_code';
    const SOURCE_CODE = 'source_code';

    /**#@-*/

    /**
     * Get source code
     *
     * @return string|null
     */
    public function getSourceCode(): ?string;

    /**
     * Set source code
     *
     * @param string|null $sourceCode
     * @return void
     */
    public function setSourceCode(?string $sourceCode): void;

    /**
     * Get type code
     *
     * @return string|null
     */
    public function getTypeCode(): ?string;

    /**
     * Set type code
     *
     * @param string|null $sourceCode
     * @return void
     */
    public function setTypeCode(?string $sourceCode): void;

    /**
     * Retrieve existing extension attributes object
     *
     * @return \Magento\InventoryApi\Api\Data\SourceTypeLinkExtensionInterface|null
     */
    public function getExtensionAttributes(): ?\Magento\InventoryApi\Api\Data\SourceTypeLinkExtensionInterface;

    /**
     * Set an extension attributes object
     *
     * @param \Magento\InventoryApi\Api\Data\SourceTypeLinkExtensionInterface $extensionAttributes
     * @return void
     */
    public function setExtensionAttributes(
        \Magento\InventoryApi\Api\Data\SourceTypeLinkExtensionInterface $extensionAttributes
    ): void;
}
