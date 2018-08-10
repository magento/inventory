<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurationApi\Api;

/**
 * Get max sale qty on the level of StockItem/Stock/Global
 *
 * @api
 */
interface GetMaxSaleQtyConfigurationValueInterface
{
    /**
     * @param string $sku
     * @param int $stockId
     * @return float|null
     */
    public function forStockItem(string $sku, int $stockId): ?float;

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
