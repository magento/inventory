<?php
/**
 * Copyright 2021 Adobe
 * All Rights Reserved.
 */

namespace Magento\InventorySales\Model\ResourceModel\IsStockItemSalableCondition;

use Magento\Framework\DB\Select;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\CatalogInventory\Api\StockConfigurationInterface;

class ReservationsCondition implements GetIsStockItemSalableConditionInterface
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
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute(Select $select): string
    {
        $globalMinQty = $this->configuration->getMinQty();
        return 'reservations.reservation_qty IS NULL OR (SUM(source_item.'
            . SourceItemInterface::QUANTITY
            . ') + reservations.reservation_qty) > '
            . $globalMinQty;
    }
}
