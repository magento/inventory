<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Model\ResourceModel;

/**
 * Get assigned Websites for Stock (Service Provider Interface - SPI)
 * Provide own implementation of this interface if you would like to replace channels management strategy
 *
 * @api
 */
interface GetAssignedSalesChannelsDataForStockInterface
{
    /**
     * Get linked sales channels for Stock
     *
     * @param int $stockId
     *
     * @return array
     */
    public function execute(int $stockId): array;
}
