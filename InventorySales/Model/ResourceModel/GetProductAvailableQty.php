<?php
/**
 * Copyright 2022 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;
use Magento\Inventory\Model\ResourceModel\Source;
use Magento\Inventory\Model\ResourceModel\SourceItem;
use Magento\Inventory\Model\ResourceModel\StockSourceLink;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\Data\StockSourceLinkInterface;
use Magento\InventorySalesApi\Model\GetProductAvailableQtyInterface;
use Zend_Db_Expr;

class GetProductAvailableQty implements GetProductAvailableQtyInterface
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
    public function execute(string $sku, int $stockId): ?float
    {
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()->from(
            ['issl' => $this->resourceConnection->getTableName(StockSourceLink::TABLE_NAME_STOCK_SOURCE_LINK)],
            []
        )->joinInner(
            ['is' => $this->resourceConnection->getTableName(Source::TABLE_NAME_SOURCE)],
            sprintf('issl.%s = is.%s', StockSourceLinkInterface::SOURCE_CODE, SourceInterface::SOURCE_CODE),
            []
        )->joinInner(
            ['isi' => $this->resourceConnection->getTableName(SourceItem::TABLE_NAME_SOURCE_ITEM)],
            sprintf('issl.%s = isi.%s', StockSourceLinkInterface::SOURCE_CODE, SourceItemInterface::SOURCE_CODE),
            []
        )->where(
            sprintf('issl.%s = ?', StockSourceLinkInterface::STOCK_ID),
            $stockId
        )->where(
            sprintf('is.%s = ?', SourceInterface::ENABLED),
            1
        )->where(
            sprintf('isi.%s = ?', SourceItemInterface::SKU),
            $sku
        )->where(
            sprintf('isi.%s = ?', SourceItemInterface::STATUS),
            SourceItemInterface::STATUS_IN_STOCK
        )->columns(
            [
                'count' => new \Zend_Db_Expr('COUNT(*)'),
                'quantity' => new Zend_Db_Expr(sprintf('SUM(isi.%s)', SourceItemInterface::QUANTITY)),
            ]
        );

        $row = $connection->fetchRow($select);

        return $row && $row['count'] > 0 ? (float) $row['quantity'] : null;
    }
}
