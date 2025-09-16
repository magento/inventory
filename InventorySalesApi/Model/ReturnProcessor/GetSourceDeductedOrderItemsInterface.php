<?php
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventorySalesApi\Model\ReturnProcessor;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\InventorySalesApi\Model\ReturnProcessor\Result\SourceDeductedOrderItemsResult;

/**
 * Service which return deducted items per source
 *
 * @api
 */
interface GetSourceDeductedOrderItemsInterface
{
    /**
     * Returns an array of deducted items per source for a given order and items marked for return to stock.
     *
     * @param OrderInterface $order
     * @param array $returnToStockItems
     * @return SourceDeductedOrderItemsResult[]
     */
    public function execute(OrderInterface $order, array $returnToStockItems): array;
}
