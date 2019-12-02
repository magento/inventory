<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySalesApi\Api;

/**
 * Service which detects whether a given Products quantities is salable for a given Stock (stock data + reservations).
 *
 * @api
 */
interface AreProductsSalableForRequestedQtyInterface
{
    /**
     * Get whether products are salable in requested Qty for given set of SKUs in specified stock.
     *
     * @param array $skuRequests array('sku' => 'quantity', ..., ...)
     * @param int $stockId
     * @return \Magento\InventorySalesApi\Api\Data\ProductSalableResultInterface[]
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(array $skuRequests, int $stockId): array;
}
