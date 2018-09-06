<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurationAdminUi\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;
use Magento\Inventory\Model\ResourceModel\StockSourceLink;
use Magento\InventoryApi\Api\Data\StockSourceLinkInterface;

/**
 * Get stock ids related to given source code.
 */
class GetStockIdsBySourceCode
{
    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @param ResourceConnection $resource
     */
    public function __construct(ResourceConnection $resource)
    {
        $this->resource = $resource;
    }

    /**
     * @param string $sourceCode
     * @return array
     */
    public function execute(string $sourceCode): array
    {
        $connection = $this->resource->getConnection();
        $select = $connection->select()
            ->from(
                $this->resource->getTableName(StockSourceLink::TABLE_NAME_STOCK_SOURCE_LINK),
                StockSourceLinkInterface::STOCK_ID
            )
            ->where(StockSourceLinkInterface::SOURCE_CODE . ' =?', $sourceCode);

        return $connection->fetchCol($select);
    }
}