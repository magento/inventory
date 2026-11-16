<?php
/**
 * Copyright 2021 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\Inventory\Model\ResourceModel\SourceItem;

use Magento\Framework\App\ResourceConnection;
use Magento\Inventory\Model\ResourceModel\SourceItem as SourceItemResourceModel;
use Magento\InventoryApi\Api\Data\SourceItemInterface;

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
     * When an item carries an 'out_of_stock_threshold', the source item is also set out of stock
     * if its new quantity is at or below that threshold. The status is never set back in stock here.
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
            $newQuantity = 'quantity - ' . $decrementItem['qty_to_decrement'];
            $data = [];
            if (isset($decrementItem['out_of_stock_threshold'])) {
                // status must be assigned before quantity, so that it is compared against the quantity
                // as it was before this update (MySQL applies SET assignments from left to right)
                $data['status'] = new \Zend_Db_Expr(
                    $connection->quoteInto(
                        'CASE WHEN ' . $newQuantity . ' <= ? THEN '
                        . SourceItemInterface::STATUS_OUT_OF_STOCK . ' ELSE status END',
                        (float)$decrementItem['out_of_stock_threshold']
                    )
                );
            }
            $data['quantity'] = new \Zend_Db_Expr($newQuantity);
            $connection->update([$tableName], $data, $where);
        }
    }
}
