<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySalesApi\Api;

use Magento\Framework\Exception\LocalizedException;

/**
 * Service which detects whether a given products quantities are salable for a given stock (stock data + reservations).
 *
 * @api
 */
interface AreProductsSalableForRequestedQtyInterface
{
    /**
     * Get whether products are salable in requested Qty for given set of SKUs in specified stock.
     *
     * @param \Magento\InventorySalesApi\Api\Data\IsProductSalableForRequestedQtyRequestInterface[] $skuRequests
     * @param int $stockId
     * @return \Magento\InventorySalesApi\Api\Data\IsProductSalableForRequestedQtyResultInterface[]
     * @throws LocalizedException
     */
    public function execute(array $skuRequests, int $stockId): array;
}
