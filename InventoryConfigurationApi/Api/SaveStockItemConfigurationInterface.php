<?php
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurationApi\Api;

/**
 * Save stock item configuration data
 *
 * @api
 */
interface SaveStockItemConfigurationInterface
{
    /**
     * Saves stock item configuration for a given SKU and stock ID using the provided configuration object.
     *
     * @param string $sku
     * @param int $stockId
     * @param \Magento\InventoryConfigurationApi\Api\Data\StockItemConfigurationInterface $stockItemConfiguration
     * @return void
     */
    public function execute(
        string $sku,
        int $stockId,
        \Magento\InventoryConfigurationApi\Api\Data\StockItemConfigurationInterface $stockItemConfiguration
    ): void;
}
