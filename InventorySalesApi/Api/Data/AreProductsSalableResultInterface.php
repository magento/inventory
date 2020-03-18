<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySalesApi\Api\Data;

use Magento\Framework\Api\ExtensibleDataInterface;

/**
 * Represents result of "are products salable" interface.
 *
 * @api
 */
interface AreProductsSalableResultInterface extends ExtensibleDataInterface
{
    /**
     * Retrieve salable results.
     *
     * @return \Magento\InventorySalesApi\Api\Data\IsProductSalableResultInterface[]
     */
    public function getSalable(): array;

    /**
     * Retrieve existing extension attributes object.
     *
     * @return \Magento\InventorySalesApi\Api\Data\AreProductsSalableResultExtensionInterface|null
     */
    public function getExtensionAttributes(): ?AreProductsSalableResultExtensionInterface;
}
