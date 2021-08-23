<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Indexer;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\DB\Select;
use Magento\Inventory\Model\ResourceModel\Source as SourceResourceModel;
use Magento\Inventory\Model\ResourceModel\SourceItem as SourceItemResourceModel;
use Magento\Inventory\Model\ResourceModel\StockSourceLink as StockSourceLinkResourceModel;
use Magento\Inventory\Model\StockSourceLink;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventorySales\Model\ResourceModel\IsStockItemSalableCondition\GetIsStockItemSalableConditionInterface;

/**
 * Prepare select for data provider
 */
class SelectBuilder implements SelectBuilderInterface
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var GetIsStockItemSalableConditionInterface
     */
    private $getIsStockItemSalableCondition;

    /**
     * @var string
     */
    private $productTableName;

    /**
     * @param ResourceConnection $resourceConnection
     * @param GetIsStockItemSalableConditionInterface $getIsStockItemSalableCondition
     * @param string $productTableName
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        GetIsStockItemSalableConditionInterface $getIsStockItemSalableCondition,
        string $productTableName
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->getIsStockItemSalableCondition = $getIsStockItemSalableCondition;
        $this->productTableName = $productTableName;
    }

    /**
     * Build select based on stockId
     *
     * @param int $stockId
     * @return Select
     */
    public function execute(int $stockId): Select
    {
        $connection = $this->resourceConnection->getConnection();
        $sourceItemTable = $this->resourceConnection->getTableName(SourceItemResourceModel::TABLE_NAME_SOURCE_ITEM);

        $quantityExpression = (string)$this->resourceConnection->getConnection()->getCheckSql(
            'source_item.' . SourceItemInterface::STATUS . ' = ' . SourceItemInterface::STATUS_OUT_OF_STOCK,
            0,
            'source_item.' . SourceItemInterface::QUANTITY
        );
        $sourceCodes = $this->getSourceCodes($stockId);

        $reservationsTableName = 'reservations_temp_for_stock_' . $stockId;
        $table = $connection->newTable($reservationsTableName);
        $table->addColumn(
            'sku',
            Table::TYPE_TEXT,
            64,
            [
                Table::OPTION_PRIMARY => true,
                Table::OPTION_NULLABLE => false,
            ],
            'Sku'
        );
        $table->addColumn(
            'reservation_qty',
            Table::TYPE_DECIMAL,
            null,
            [
                Table::OPTION_UNSIGNED => false,
                Table::OPTION_NULLABLE => false,
                Table::OPTION_DEFAULT => 0,
                Table::OPTION_PRECISION => 10,
                Table::OPTION_SCALE => 4,
            ],
            'Reservation Qty'
        );
        $table->addIndex(
            'index_sku_qty',
            ['sku'],
            ['type' => AdapterInterface::INDEX_TYPE_INDEX]
        );
        $connection->createTemporaryTable($table);

        $reservationsData = $connection->select();
        $reservationsData->from(
            ['reservations' => $this->resourceConnection->getTableName('inventory_reservation')],
            [
                'sku',
                'reservation_qty' => 'SUM(reservations.quantity)'
            ]
        );
        $reservationsData->group(['sku', 'stock_id']);

        $insRes = $connection->insertFromSelect($reservationsData, $reservationsTableName);
        $connection->query($insRes);

        $select = $connection->select();
        $select->joinLeft(
            ['product' => $this->resourceConnection->getTableName($this->productTableName)],
            'product.sku = source_item.' . SourceItemInterface::SKU,
            []
        )->joinLeft(
            ['legacy_stock_item' => $this->resourceConnection->getTableName('cataloginventory_stock_item')],
            'product.entity_id = legacy_stock_item.product_id',
            []
        )->joinLeft(
            ['reservations_qty' => $this->resourceConnection->getTableName($reservationsTableName)],
            ' source_item.' . SourceItemInterface::SKU . ' = reservations_qty.sku',
            []
        );

        $select->from(
            ['source_item' => $sourceItemTable],
            [
                SourceItemInterface::SKU,
                IndexStructure::QUANTITY => 'SUM(' . $quantityExpression . ')',
                IndexStructure::IS_SALABLE => $this->getIsStockItemSalableCondition->execute($select),
            ]
        )
            ->where('source_item.' . SourceItemInterface::SOURCE_CODE . ' IN (?)', $sourceCodes)
            ->group(['source_item.' .SourceItemInterface::SKU]);

        return $select;
    }

    /**
     * Get all enabled sources related to stock
     *
     * @param int $stockId
     * @return array
     */
    private function getSourceCodes(int $stockId): array
    {
        $connection = $this->resourceConnection->getConnection();
        $sourceTable = $this->resourceConnection->getTableName(SourceResourceModel::TABLE_NAME_SOURCE);
        $sourceStockLinkTable = $this->resourceConnection->getTableName(
            StockSourceLinkResourceModel::TABLE_NAME_STOCK_SOURCE_LINK
        );

        $select = $connection->select()
            ->from(['source' => $sourceTable], [SourceInterface::SOURCE_CODE])
            ->joinInner(
                ['stock_source_link' => $sourceStockLinkTable],
                'source.' . SourceItemInterface::SOURCE_CODE . ' = stock_source_link.' . StockSourceLink::SOURCE_CODE,
                []
            )
            ->where('stock_source_link.' . StockSourceLink::STOCK_ID . ' = ?', $stockId)
            ->where(SourceInterface::ENABLED . ' = ?', 1);

        $sourceCodes = $connection->fetchCol($select);
        return $sourceCodes;
    }
}
