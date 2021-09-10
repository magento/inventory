<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;
use Magento\InventoryApi\Api\Data\SourceItemInterface;

/**
 * Check if product has source items with the in stock status.
 */
class GetIsAnySourceItemInStock
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
     * Get if product has source items with the in stock status.
     *
     * @param string $sku
     * @param int $stockId
     * @return bool
     */
    public function execute(string $sku, int $stockId): bool
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
            'isi.sku = ?',
            $sku
        )->where(
            'isi.status = ?',
            SourceItemInterface::STATUS_IN_STOCK
        )->columns(
            ['count' => new \Zend_Db_Expr('COUNT(*)')]
        );
        $count = (int) $connection->fetchOne($select);

        return $count > 0;
    }
}
