<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;

/**
 * Update old Website Code values in inventory_stock_sales_channel table with the new one.
 */
class UpdateSalesChannelsWebsiteCode
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        ResourceConnection $resourceConnection
    ) {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Replace all occurrences of old Website Code value in inventory_stock_sales_channel table with the new one.
     */
    public function execute(string $newWebsiteCode, string $oldWebsiteCode)
    {
        $connection = $this->resourceConnection->getConnection();

        $connection->update(
            $tableName = $this->resourceConnection->getTableName('inventory_stock_sales_channel'),
            [
                SalesChannelInterface::CODE => $newWebsiteCode,
            ],
            [
                SalesChannelInterface::CODE . ' = ?' => $oldWebsiteCode,
            ]
        );
    }
}
