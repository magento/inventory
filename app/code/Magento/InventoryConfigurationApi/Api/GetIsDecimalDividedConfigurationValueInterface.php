<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurationApi\Api;

/**
 * Get is decimal divided on the level of StockItem
 *
 * @api
 */
interface GetIsDecimalDividedConfigurationValueInterface
{
    /**
     * @param string $sku
     * @param int $stockId
     * @return int|null
     */
    public function forStockItem(string $sku, int $stockId): ?int;
}
