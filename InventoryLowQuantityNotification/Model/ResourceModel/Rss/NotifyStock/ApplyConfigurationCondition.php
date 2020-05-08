<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryLowQuantityNotification\Model\ResourceModel\Rss\NotifyStock;

use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;

class ApplyConfigurationCondition
{
    /**
     * @var StockConfigurationInterface
     */
    private $configuration;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @param StockConfigurationInterface $stockConfiguration
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        StockConfigurationInterface $stockConfiguration,
        ResourceConnection $resourceConnection
    ) {
        $this->configuration = $stockConfiguration;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Add additional condition to the select.
     *
     * @param Select $select
     * @return void
     */
    public function execute(Select $select)
    {
        $configManageStock = $this->configuration->getManageStock();
        $configNotifyStockQty = $this->configuration->getNotifyStockQty();

        $connection = $this->resourceConnection->getConnection();
        $qtyCondition = $connection->getIfNullSql(
            'source_item_config.notify_stock_qty',
            $configNotifyStockQty
        );

        $globalManageStockEnabledCondition = implode(
            ' ' . Select::SQL_AND . ' ',
            [
                $connection->prepareSqlCondition('legacy_stock_item.use_config_manage_stock', 1),
                $connection->prepareSqlCondition($configManageStock, 1),
                $connection->prepareSqlCondition('main_table.quantity', ['lt' => $qtyCondition]),
            ]
        );
        $globalManageStockDisabledCondition = implode(
            ' ' . Select::SQL_AND . ' ',
            [
                $connection->prepareSqlCondition('legacy_stock_item.use_config_manage_stock', 0),
                $connection->prepareSqlCondition('legacy_stock_item.manage_stock', 1),
                $connection->prepareSqlCondition('main_table.quantity', ['lt' => $qtyCondition]),
            ]
        );

        $condition = implode(
            ') ' . Select::SQL_OR . ' (',
            [
                $globalManageStockEnabledCondition,
                $globalManageStockDisabledCondition,
            ]
        );

        $select->where('(' . $condition . ')');
    }
}
