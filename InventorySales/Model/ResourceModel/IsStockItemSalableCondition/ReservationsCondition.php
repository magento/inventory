<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\InventorySales\Model\ResourceModel\IsStockItemSalableCondition;

use Magento\Framework\DB\Select;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\Framework\App\ResourceConnection;

class ReservationsCondition implements GetIsStockItemSalableConditionInterface
{
    /**
     * @var StockConfigurationInterface
     */
    private $configuration;

    /**
     * @param StockConfigurationInterface $configuration
     * @param ResourceConnection $resourceConnection
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
        return 'reservation_qty IS NULL OR (source_item.' . SourceItemInterface::QUANTITY . ' + reservation_qty) > ' . $globalMinQty;

    }
}
