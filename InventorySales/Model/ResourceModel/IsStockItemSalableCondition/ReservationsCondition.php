<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\InventorySales\Model\ResourceModel\IsStockItemSalableCondition;

use Magento\Framework\DB\Select;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryApi\Api\Data\StockSourceLinkInterface;
use Magento\InventoryReservationsApi\Model\ReservationInterface;
use Magento\Inventory\Model\ResourceModel\StockSourceLink;
use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\Framework\App\ResourceConnection;

class ReservationsCondition implements GetIsStockItemSalableConditionInterface
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
     * @param StockConfigurationInterface $configuration
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        StockConfigurationInterface $configuration,
        ResourceConnection $resourceConnection
    ) {
        $this->configuration = $configuration;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @inheritdoc
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute(Select $select): string
    {
        $stockSourceLinkTable = $this->resourceConnection->getTableName(StockSourceLink::TABLE_NAME_STOCK_SOURCE_LINK);
        $reservationTable = $this->resourceConnection->getTableName('inventory_reservation');
        $globalMinQty = (float)$this->configuration->getMinQty();
        $itemQty = 'source_item.quantity';

        $select->joinLeft(
            ['stock_source_link' => $stockSourceLinkTable],
            'source_item.' . SourceInterface::SOURCE_CODE . '=' . 'stock_source_link.' . SourceInterface::SOURCE_CODE,
            null
        )->joinLeft(
            ['inventory_reservation' => $reservationTable],
            'product.sku = inventory_reservation.' . ReservationInterface::SKU
            . ' AND stock_source_link.' . StockSourceLinkInterface::STOCK_ID . ' = inventory_reservation.' . ReservationInterface::STOCK_ID,
            []
        );

        $qtyWithReservation = '(' . $itemQty . ' + SUM(inventory_reservation.' . ReservationInterface::QUANTITY . '))';
        return 'SUM(inventory_reservation.' . ReservationInterface::QUANTITY . ') IS NULL '
            . 'OR ' . $qtyWithReservation . ' > ' . $globalMinQty;

    }
}
