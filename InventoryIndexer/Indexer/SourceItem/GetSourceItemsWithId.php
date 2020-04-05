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
 * Get SourceItem records from datastore from array of SourceItems
 */
class GetSourceItemsWithId
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
     * Retrieve source items by ids.
     *
     * @param SourceItemInterface[] $sourceItems
     * @return array
     */
    public function execute(array $sourceItems): array
    {
        $connection = $this->resourceConnection->getConnection();
        $skusBySourceCode = [];
        $sourceItemRecords = [];
        foreach ($sourceItems as $sourceItem) {
            $skusBySourceCode[$sourceItem->getSourceCode()][] = $sourceItem->getSku();
        }
        foreach ($skusBySourceCode as $sourceCode => $skus) {
            $select = $connection->select()
                ->from(
                    $this->resourceConnection->getTableName(SourceItemResourceModel::TABLE_NAME_SOURCE_ITEM),
                    [SourceItemResourceModel::ID_FIELD_NAME, SourceItemInterface::SOURCE_CODE, SourceItemInterface::SKU]
                )->where('sku IN (?)', $skus)->where('source_code = ?', $sourceCode);
            $item = $connection->fetchAssoc($select);
            if (empty($item)) {
                // no guarantee we got in first.
                continue;
            }
            $sourceItemRecords += $item;
        }

        return $sourceItemRecords;
    }
}
