<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\Inventory\Model\ResourceModel;

use Magento\InventoryApi\Model\AreProductsAssignedToStockInterface;
use Magento\Framework\App\ResourceConnection;

class AreProductsAssignedToStock implements AreProductsAssignedToStockInterface
{
    /**
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        private readonly ResourceConnection $resourceConnection
    ) {
    }

    /**
     * @inheritdoc
     */
    public function execute(array $skus, int $stockId): array
    {
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()->from(
            ['issl' => $this->resourceConnection->getTableName(StockSourceLink::TABLE_NAME_STOCK_SOURCE_LINK)],
            []
        )->joinInner(
            ['isi' => $this->resourceConnection->getTableName(SourceItem::TABLE_NAME_SOURCE_ITEM)],
            'issl.source_code = isi.source_code',
            []
        )->where(
            'issl.stock_id = ?',
            $stockId
        )->where(
            'isi.sku IN (?)',
            $skus
        )->group(
            ['isi.sku']
        )->columns(
            [
                'sku' => 'isi.sku',
                'count' => new \Zend_Db_Expr('COUNT(*)'),
            ]
        );

        $result = array_fill_keys($skus, false);
        foreach ($connection->fetchAll($select) as $item) {
            $result[$item['sku']] = $item['count'] > 0;
        }
        return $result;
    }
}
