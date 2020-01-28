<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\InventoryCatalogApi\Api\Data;

use Magento\Framework\Api\ExtensibleDataInterface;
use Magento\InventoryCatalogApi\Api\Data\ResultExtensionInterface;

/**
 * Operation result object that contains status of the operation.
 *
 * @api
 */
interface ResultInterface extends ExtensibleDataInterface
{
    /**
     * Is operation result successful.
     *
     * @return bool
     */
    public function isSuccessful() : bool;

    /**
     * Get error information for failed operations.
     *
     * @return array
     */
    public function getFailed() : array;

    /**
     * Set Extension Attributes for Operation result.
     *
     * @param \Magento\InventoryCatalogApi\Api\Data\ResultExtensionInterface|null $extensionAttributes
     *
     * @return void
     */
    public function setExtensionAttributes(?ResultExtensionInterface $extensionAttributes): void;

    /**
     * Get Extension Attributes of Operation result.
     *
     * @return \Magento\InventoryCatalogApi\Api\Data\ResultExtensionInterface|null
     */
    public function getExtensionAttributes(): ?ResultExtensionInterface;
}
