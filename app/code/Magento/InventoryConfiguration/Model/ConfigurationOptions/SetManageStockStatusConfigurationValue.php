<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfiguration\Model\ConfigurationOptions;

use Magento\InventoryConfigurationApi\Api\SetManageStockStatusConfigurationValueInterface;
use Magento\InventoryConfigurationApi\Api\StockItemConfigurationInterface;

class SetManageStockStatusConfigurationValue implements SetManageStockStatusConfigurationValueInterface
{
    /**
     * @param string $sku
     * @param int $stockId
     * @param int|null $manageStock
     * @return void
     */
    public function forStockItem(string $sku, int $stockId, ?int $manageStock): void
    {
        // TODO: Implement forStockItem() method.
    }

    /**
     * @param int $stockId
     * @param int|null $manageStock
     * @return void
     */
    public function forStock(int $stockId, ?int $manageStock): void
    {
        // TODO: Implement forStock() method.
    }

    /**
     * @param int|null $manageStock
     * @return void
     */
    public function forGlobal(?int $manageStock): void
    {
        // TODO: Implement forGlobal() method.
    }
}
