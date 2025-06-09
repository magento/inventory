<?php
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurationApi\Api;

/**
 * Returns stock item configuration data by sku and stock id.
 *
 * @api
 */
interface GetStockItemConfigurationInterface
{
    /**
     * Returns stock item configuration for a given SKU and stock ID or throws an exception if not found.
     *
     * @param string $sku
     * @param int $stockId
     * @return \Magento\InventoryConfigurationApi\Api\Data\StockItemConfigurationInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\InventoryConfigurationApi\Exception\SkuIsNotAssignedToStockException
     */
    public function execute(
        string $sku,
        int $stockId
    ): \Magento\InventoryConfigurationApi\Api\Data\StockItemConfigurationInterface;
}
