<?php
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventorySalesApi\Model;

use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;

/**
 * Get assigned Sales Channels for Stock (Service Provider Interface - SPI)
 * Provide own implementation of this interface if you would like to replace channels management strategy
 *
 * @api
 */
interface GetAssignedSalesChannelsForStockInterface
{
    /**
     * Get linked sales channels for Stock
     *
     * @param int $stockId
     * @return SalesChannelInterface[]
     */
    public function execute(int $stockId): array;
}
