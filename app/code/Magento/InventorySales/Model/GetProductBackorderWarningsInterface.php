<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Model;

/**
 * Service collects warnings for item on enabled backorders with a notification
 *
 * @api
 */
interface GetProductBackorderWarningsInterface
{
    /**
     * Get backorders warnings for the item
     *
     * @param string $sku
     * @param int $stockId
     * @param float $requestedQty
     * @return array
     */
    public function execute(string $sku, int $stockId, float $requestedQty): array;
}
