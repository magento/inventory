<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Model;

use Magento\Framework\App\ResourceConnection;
use Magento\Inventory\Model\ResourceModel\SourceItem;
use Magento\Inventory\Model\ResourceModel\StockSourceLink;
use Magento\Inventory\Model\ResourceModel\Source;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\Data\StockSourceLinkInterface;
use Zend_Db_Expr;

/**
 * Service which returns aggregated quantity of a product across all active sources in the provided stock
 */
class GetProductAvailableQty
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
     * Get available quantity for given SKU and Stock
     *
     * @param string $sku
     * @param int $stockId
     * @return float
     */
    public function execute(string $sku, int $stockId): float
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
            ['quantity' => new Zend_Db_Expr(sprintf('SUM(isi.%s)', SourceItemInterface::QUANTITY))]
        );

        return (float) $connection->fetchOne($select);
    }
}
