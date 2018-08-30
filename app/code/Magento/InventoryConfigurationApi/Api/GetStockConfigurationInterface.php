<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurationApi\Api;

use Magento\InventoryConfigurationApi\Api\Data\StockItemConfigurationInterface;

/**
 * @api
 */
interface GetStockConfigurationInterface
{
    /**
     * @param string $sku
     * @param int $stockId
     * @return StockItemConfigurationInterface
     */
    public function forStockItem(string $sku, int $stockId): StockItemConfigurationInterface;

    /**
     * @param int $stockId
     * @return StockItemConfigurationInterface
     */
    public function forStock(int $stockId): StockItemConfigurationInterface;

    /**
     * @return StockItemConfigurationInterface
     */
    public function forGlobal(): StockItemConfigurationInterface;
}
