<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryApi\Api\Data\StockInterface;
use Magento\InventoryInStorePickupApi\Api\Data\PickupLocationInterface as Location;

/**
 * Verify is store pickup available for given stock.
 */
class IsStorePickUpAvailableForStock
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(ResourceConnection $resourceConnection)
    {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Verify is enabled sources with 'Use as Pickup Location' exist for given stock.
     *
     * @param int $stockId
     * @return bool
     */
    public function execute(int $stockId): bool
    {
        $adapter = $this->resourceConnection->getConnection();
        $sourceTable = $this->resourceConnection->getTableName('inventory_source');
        $sourceStockLinkTable = $this->resourceConnection->getTableName('inventory_source_stock_link');
        $query = $adapter->select()
            ->from($sourceTable)
            ->where($sourceTable . '.' . SourceInterface::ENABLED . '=?', 1)
            ->where($sourceTable . '.' . Location::IS_PICKUP_LOCATION_ACTIVE . '=?', 1)
            ->join(
                ['source_stock_link' => $sourceStockLinkTable],
                'source_stock_link.' . SourceInterface::SOURCE_CODE . ' = '
                . $sourceTable . '.' . SourceInterface::SOURCE_CODE
            )->where('source_stock_link' . '.' . StockInterface::STOCK_ID . '=?', $stockId);
        $result = $adapter->fetchAll($query);

        return !empty($result);
    }
}
