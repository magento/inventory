<?php
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */

declare(strict_types=1);

namespace Magento\InventorySalesApi\Model;

use Magento\Sales\Api\Data\OrderItemInterface;

/**
 * @api
 */
interface GetSkuFromOrderItemInterface
{
    /**
     * Gets the SKU from the provided order item.
     *
     * @param OrderItemInterface $orderItem
     * @return string
     */
    public function execute(OrderItemInterface $orderItem): string;
}
