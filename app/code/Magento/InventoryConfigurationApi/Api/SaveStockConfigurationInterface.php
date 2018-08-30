<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurationApi\Api;

use Magento\InventoryConfigurationApi\Api\Data\StockItemConfigurationInterface;

/**
 * Save stock item configuration data
 *
 * @api
 */
interface SaveStockConfigurationInterface
{
    /**
     * @param string $sku
     * @param int $stockId
     * @param StockItemConfigurationInterface $stockItemConfiguration
     * @return void
     */
    public function forStockItem(
        string $sku,
        int $stockId,
        StockItemConfigurationInterface $stockItemConfiguration
    ): void;

    /**
     * @param int $stockId
     * @param StockItemConfigurationInterface $stockItemConfiguration
     * @return void
     */
    public function forStock(int $stockId, StockItemConfigurationInterface $stockItemConfiguration): void;

    /**
     * @param StockItemConfigurationInterface $stockItemConfiguration
     * @return void
     */
    public function forGlobal(StockItemConfigurationInterface $stockItemConfiguration): void;
}
