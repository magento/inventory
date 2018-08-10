<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurationApi\Api;

/**
 * Get manage stock status on the level of StockItem/Stock/Global
 *
 * @api
 */
interface SetManageStockStatusConfigurationValueInterface
{
    /**
     * @param string $sku
     * @param int $stockId
     * @param int|null $manageStock
     * @return void
     */
    public function forStockItem(string $sku, int $stockId, ?int $manageStock): void;

    /**
     * @param int $stockId
     * @param int|null $manageStock
     * @return void
     */
    public function forStock(int $stockId, ?int $manageStock): void;

    /**
     * @param int|null $manageStock
     * @return void
     */
    public function forGlobal(?int $manageStock): void;
}
