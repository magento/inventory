<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Indexer;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Inventory\Model\ResourceModel\Source as SourceResourceModel;
use Magento\Inventory\Model\ResourceModel\SourceItem as SourceItemResourceModel;
use Magento\Inventory\Model\ResourceModel\StockSourceLink as StockSourceLinkResourceModel;
use Magento\Inventory\Model\StockSourceLink;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryIndexer\Indexer\Stock\ReservationsIndexTable;
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
     * @var ReservationsIndexTable
     */
    private $reservationsIndexTable;

    /**
     * @param ResourceConnection $resourceConnection
     * @param GetIsStockItemSalableConditionInterface $getIsStockItemSalableCondition
     * @param string $productTableName
     * @param ReservationsIndexTable $reservationsIndexTable
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        GetIsStockItemSalableConditionInterface $getIsStockItemSalableCondition,
        string $productTableName,
        ReservationsIndexTable $reservationsIndexTable
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->getIsStockItemSalableCondition = $getIsStockItemSalableCondition;
        $this->productTableName = $productTableName;
        $this->reservationsIndexTable = $reservationsIndexTable;
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
            [
                'reservations' => $this->resourceConnection->getTableName(
                    $this->reservationsIndexTable->getTableName($stockId)
                )
            ],
            ' source_item.' . SourceItemInterface::SKU . ' = reservations.sku',
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

        return $connection->fetchCol($select);
    }
}
