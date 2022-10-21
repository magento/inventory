<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;
use Magento\Inventory\Model\ResourceModel\SourceItem as SourceItemResourceModel;
use Magento\InventoryApi\Api\Data\SourceItemInterface;

class GetSourceCodesBySourceItemIds
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
     * Get source codes by source item ids
     *
     * @param array $sourceItemIds
     * @return array
     */
    public function execute(array $sourceItemIds): array
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName(SourceItemResourceModel::TABLE_NAME_SOURCE_ITEM);
        $select = $connection->select()
            ->from(
                ['source_item' => $tableName],
                [SourceItemResourceModel::ID_FIELD_NAME, SourceItemInterface::SOURCE_CODE]
            )
            ->where(
                'source_item.' . SourceItemResourceModel::ID_FIELD_NAME . ' IN (?)',
                $sourceItemIds
            );

        return $connection->fetchPairs($select);
    }
}
