<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySalesApi\Model;

/**
 * Service which returns Quantity of products available to be sold by Product SKU and Stock Id.
 *
 * Calculates the salable qty taking into account existing reservations for
 * given sku and stock id and subtracting min qty (a.k.a. "Out-of-Stock Threshold")
 * This service does not take into account the stock status in contrast with the service
 * \Magento\InventorySalesApi\Api\GetProductSalableQtyInterface
 */
interface GetSalableQtyInterface
{
    /**
     * Get Product Quantity for given SKU and Stock
     *
     * @param string $sku
     * @param int $stockId
     * @return float
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(string $sku, int $stockId): float;
}
