<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySalesApi\Model;

/**
 * Service which returns available quantity of a product in the provided stock.
 *
 * Unlike GetProductSalableQtyInterface, this service does not take into account reservations
 */
interface GetProductAvailableQtyInterface
{
    /**
     * Get available quantity for given SKU and Stock
     *
     * @param string $sku
     * @param int $stockId
     * @return float
     */
    public function execute(string $sku, int $stockId): float;
}
