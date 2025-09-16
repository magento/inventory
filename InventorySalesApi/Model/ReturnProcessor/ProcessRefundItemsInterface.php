<?php
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventorySalesApi\Model\ReturnProcessor;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\InventorySalesApi\Model\ReturnProcessor\Request\ItemsToRefundInterface;

/**
 * Refund Items
 *
 * @api
 */
interface ProcessRefundItemsInterface
{
    /**
     * Executes the refund process for order items, handling quantities and return-to-stock items.
     *
     * @param OrderInterface $order
     * @param ItemsToRefundInterface[] $itemsToRefund
     * @param array $returnToStockItems
     * @return void
     */
    public function execute(
        OrderInterface $order,
        array $itemsToRefund,
        array $returnToStockItems
    );
}
