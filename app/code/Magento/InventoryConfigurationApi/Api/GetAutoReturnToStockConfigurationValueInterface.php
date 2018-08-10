<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurationApi\Api;

/**
 * Get auto return to stock on the level of Stock/Global
 *
 * @api
 */
interface GetAutoReturnToStockConfigurationValueInterface
{
    /**
     * @param int $stockId
     * @return int|null
     */
    public function forStock(int $stockId): ?int;

    /**
     * @return int
     */
    public function forGlobal(): int;
}
