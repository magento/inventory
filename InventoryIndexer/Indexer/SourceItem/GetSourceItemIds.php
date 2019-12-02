<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Indexer\SourceItem;

use Magento\Framework\App\ResourceConnection;
use Magento\Inventory\Model\ResourceModel\SourceItem as SourceItemResourceModel;
use Magento\InventoryApi\Api\Data\SourceItemInterface;

/**
 * Get SourceItem ids from array of SourceItems
 */
class GetSourceItemIds
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
     * Retrieve source items ids.
     *
     * @param SourceItemInterface[] $sourceItems
     * @return array
     */
    public function execute(array $sourceItems): array
    {
        $connection = $this->resourceConnection->getConnection();
        $skusBySourceCode = [];
        $sourceItemIds = [[]];
        foreach ($sourceItems as $sourceItem) {
            $skusBySourceCode[$sourceItem->getSourceCode()][] = $sourceItem->getSku();
        }
        foreach ($skusBySourceCode as $sourceCode => $skus) {
            $select = $connection->select()
                ->from(
                    $this->resourceConnection->getTableName(SourceItemResourceModel::TABLE_NAME_SOURCE_ITEM),
                    [SourceItemResourceModel::ID_FIELD_NAME]
                )->where('sku IN (?)', $skus)->where('source_code = ?', $sourceCode);
            $sourceItemIds[] = $connection->fetchCol($select);
        }
        $sourceItemIds = array_merge(...$sourceItemIds);

        return $sourceItemIds;
    }
}
