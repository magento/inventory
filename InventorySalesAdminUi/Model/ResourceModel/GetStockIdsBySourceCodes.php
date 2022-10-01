<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySalesAdminUi\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;
use Magento\InventoryApi\Api\Data\StockSourceLinkInterface;

/**
 * Class for getting stock ids by source codes.
 */
class GetStockIdsBySourceCodes
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
     * Get stock ids by source codes.
     *
     * @param array $sourceCodes
     * @return array
     */
    public function execute(array $sourceCodes): array
    {
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()
            ->distinct()
            ->from(
                $this->resourceConnection->getTableName('inventory_source_stock_link'),
                [StockSourceLinkInterface::STOCK_ID]
            )
            ->where(
                StockSourceLinkInterface::SOURCE_CODE . ' IN (?)',
                $sourceCodes
            );
        $stockIds = $connection->fetchCol($select);

        return $stockIds;
    }
}
