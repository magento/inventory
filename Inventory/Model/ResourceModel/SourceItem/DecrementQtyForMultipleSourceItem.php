<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Inventory\Model\ResourceModel\SourceItem;

use Magento\Framework\App\ResourceConnection;
use Magento\Inventory\Model\ResourceModel\SourceItem as SourceItemResourceModel;

/**
 * Implementation of SourceItem decrement qty operation for specific db layer
 */
class DecrementQtyForMultipleSourceItem
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
     * Decrement qty for source item
     *
     * @param array $decrementItems
     * @return void
     */
    public function execute(array $decrementItems): void
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName(SourceItemResourceModel::TABLE_NAME_SOURCE_ITEM);
        if (!count($decrementItems)) {
            return;
        }
        foreach ($decrementItems as $decrementItem) {
            $sourceItem = $decrementItem['source_item'];
            $where = [
                'source_code = ?' => $sourceItem->getSourceCode(),
                'sku = ?' => $sourceItem->getSku()
            ];
            $connection->update(
                [$tableName],
                ['quantity' => new \Zend_Db_Expr('quantity - ' . $decrementItem['qty_to_decrement'])],
                $where
            );
        }
    }
}
