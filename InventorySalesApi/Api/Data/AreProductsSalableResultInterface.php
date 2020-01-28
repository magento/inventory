<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySalesApi\Api\Data;

use Magento\Framework\Api\ExtensibleDataInterface;

/**
 * Represents result of service Magento\InventorySalesApi\Api\AreProductsSalableForRequestedQtyInterface::execute()
 *
 * @api
 */
interface AreProductsSalableResultInterface extends ExtensibleDataInterface
{
    /**
     * Retrieve is salable results.
     *
     * @return \Magento\InventorySalesApi\Api\Data\ProductSalableResultInterface[]
     */
    public function getAreSalable(): array;

    /**
     * Retrieve existing extension attributes object.
     *
     * @return \Magento\InventorySalesApi\Api\Data\AreProductsSalableResultExtensionInterface|null
     */
    public function getExtensionAttributes(): ?AreProductsSalableResultExtensionInterface;

    /**
     * Set an extension attributes object.
     *
     * @param \Magento\InventorySalesApi\Api\Data\AreProductsSalableResultExtensionInterface $extensionAttributes
     * @return void
     */
    public function setExtensionAttributes(
        \Magento\InventorySalesApi\Api\Data\AreProductsSalableResultExtensionInterface $extensionAttributes
    ): void;
}
