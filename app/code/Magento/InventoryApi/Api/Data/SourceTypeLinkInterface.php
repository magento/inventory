<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryApi\Api\Data;

use Magento\Framework\Api\ExtensibleDataInterface;

/**
 * Represents relation between some physical storage and shipping method
 *
 * Used fully qualified namespaces in annotations for proper work of WebApi request parser
 *
 * @api
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
     * Get carrier code
     *
     * @return string|null
     */
    public function getSourceCode(): ?string;

    /**
     * Set carrier code
     *
     * @param string|null $sourceCode
     * @return void
     */
    public function setSourceCode(?string $sourceCode): void;

    /**
     * Get carrier code
     *
     * @return string|null
     */
    public function getTypeCode(): ?string;

    /**
     * Set carrier code
     *
     * @param string|null $sourceCode
     * @return void
     */
    public function setTypeCode(?string $sourceCode): void;
}
