<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Model\ResourceModel\IsStockItemSalableCondition;

use Magento\Framework\DB\Select;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\CatalogInventory\Api\StockConfigurationInterface;

/**
 * Condition that checks minimum qty and reservations
 */
class MinQtyStockWithReservationsCondition implements GetIsStockItemSalableConditionInterface
{
    /**
     * @var StockConfigurationInterface
     */
    private $configuration;

    /**
     * @param StockConfigurationInterface $configuration
     */
    public function __construct(
        StockConfigurationInterface $configuration
    ) {
        $this->configuration = $configuration;
    }

    /**
     * @inheritdoc
     */
    public function execute(Select $select): string
    {
        $globalMinQty = (float) $this->configuration->getMinQty();
        $itemMinQty = 'legacy_stock_item.min_qty';
        $inStockQty = (string) $select->getConnection()->getCheckSql(
            'source_item.' . SourceItemInterface::STATUS . ' = ' . SourceItemInterface::STATUS_OUT_OF_STOCK,
            0,
            'source_item.' . SourceItemInterface::QUANTITY
        );
        $inStockQty = 'SUM(' . $inStockQty . ')';
        $minQty =  (string) $select->getConnection()->getCheckSql(
            'legacy_stock_item.use_config_min_qty = 1',
            $globalMinQty,
            $itemMinQty
        );
        $reservationQty =  (string) $select->getConnection()->getCheckSql(
            'reservations.reservation_qty IS NULL',
            0,
            'reservations.reservation_qty'
        );

        return "$inStockQty + $reservationQty - $minQty > 0";
    }
}
