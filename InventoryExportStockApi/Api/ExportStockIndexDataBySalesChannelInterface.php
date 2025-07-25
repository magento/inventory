<?php
/**
 * Copyright 2019 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryExportStockApi\Api;

/**
 * Provides stock index export based on raw data contained in the stock index.
 * @api
 */
interface ExportStockIndexDataBySalesChannelInterface
{
    /**
     * Provides stock index export from inventory_stock_% table by Sales Channel
     *
     * @param \Magento\InventorySalesApi\Api\Data\SalesChannelInterface $salesChannel
     * @return \Magento\InventoryExportStockApi\Api\Data\ProductStockIndexDataInterface[]
     */
    public function execute(\Magento\InventorySalesApi\Api\Data\SalesChannelInterface $salesChannel): array;
}
