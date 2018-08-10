<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurationApi\Api;

/**
 * Get enable qty increments on the level of StockItem/Stock/Global
 *
 * @api
 */
interface GetEnableQtyIncrementsConfigurationValueInterface
{
    /**
     * @param string $sku
     * @param int $stockId
     * @return int|null
     */
    public function forStockItem(string $sku, int $stockId): ?int;

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
