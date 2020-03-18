<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySalesApi\Api\Data;

use Magento\Framework\Api\ExtensibleDataInterface;

/**
 * Represents "is product salable" result interface.
 *
 * @api
 */
interface IsProductSalableResultInterface extends ExtensibleDataInterface
{
    /**
     * @return string
     */
    public function getSku(): string;

    /**
     * @return bool
     */
    public function isSalable(): bool;

    /**
     * Retrieve existing extension attributes object.
     *
     * @return \Magento\InventorySalesApi\Api\Data\IsProductSalableResultExtensionInterface|null
     */
    public function getExtensionAttributes(): ?IsProductSalableResultExtensionInterface;
}
