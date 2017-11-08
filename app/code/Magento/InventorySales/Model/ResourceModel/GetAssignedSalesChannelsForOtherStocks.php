<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Model\ResourceModel;

use Magento\InventorySales\Setup\Operation\CreateSalesChannelTable;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\Framework\App\ResourceConnection;

/**
 * This resource model is responsible for retrieving Sources by store id with out current stock
 * Used by Service Contracts that are agnostic to the Data Access Layer
 */
class GetAssignedSalesChannelsForOtherStocks implements GetAssignedSalesChannelsForOtherStocksInterface
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
     * Returns the linked sales channel by given a stock id with out current channel
     *
     * @param int    $stockId
     * @param string $channelCode
     *
     * @return array
     *
     */
    public function execute(int $stockId, string $channelCode):array
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName(CreateSalesChannelTable::TABLE_NAME_SALES_CHANNEL);

        $select = $connection->select()
                             ->from($tableName)
                             ->where(SalesChannelInterface::CODE . ' = ?', $channelCode)
                             ->where(SalesChannelInterface::STOCK_ID . ' != ?', $stockId);

        $stockIds = $connection->fetchAll($select);
        return $stockIds;
    }
}
