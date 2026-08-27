<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventorySalesApi\Model\GetProductAvailableQtyBySkuListInterface;

class GetProductAvailableQtyBySkuList implements GetProductAvailableQtyBySkuListInterface
{
    /**
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        private readonly ResourceConnection $resourceConnection
    ) {
    }

    /**
     * @inheritDoc
     */
    public function execute(array $skus, int $stockId): array
    {
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()->from(
            ['issl' => $this->resourceConnection->getTableName('inventory_source_stock_link')],
            []
        )->joinInner(
            ['is' => $this->resourceConnection->getTableName('inventory_source')],
            'issl.source_code = is.source_code',
            []
        )->joinInner(
            ['isi' => $this->resourceConnection->getTableName('inventory_source_item')],
            'issl.source_code = isi.source_code',
            []
        )->where(
            'issl.stock_id = ?',
            $stockId
        )->where(
            'is.enabled = ?',
            1
        )->where(
            'isi.sku IN (?)',
            $skus
        )->where(
            'isi.status = ?',
            SourceItemInterface::STATUS_IN_STOCK
        )->group(
            ['isi.sku']
        )->columns(
            [
                'sku' => 'isi.sku',
                'count' => new \Zend_Db_Expr('COUNT(*)'),
                'quantity' => new \Zend_Db_Expr(sprintf('SUM(isi.%s)', SourceItemInterface::QUANTITY)),
            ]
        );
        $result = array_fill_keys($skus, null);
        foreach ($connection->fetchAll($select) as $item) {
            if ($item['count'] > 0) {
                $result[$item['sku']] = (float) $item['quantity'];
            }
        }
        return $result;
    }
}
