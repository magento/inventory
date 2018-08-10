<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurationApi\Api;

/**
 * Get stock threshold qty on the level of Stock/Global
 *
 * @api
 */
interface GetStockThresholdQtyConfigurationValueInterface
{
    /**
     * @param int $stockId
     * @return float|null
     */
    public function forStock(int $stockId): ?float;

    /**
     * @return float
     */
    public function forGlobal(): float;
}
