<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySalesApi\Api;

use Magento\InventorySalesApi\Api\Data\AreProductsSalableResultInterface;

/**
 * Service which detects whether products are salable for a given stock (stock data + reservations)
 *
 * @api
 */
interface AreProductsSalableInterface
{
    /**
     * Get are products salable for given SKUs and given Stock.
     *
     * @param string $skus in format "sku1,sku2,..."
     * @param int $stockId
     * @return \Magento\InventorySalesApi\Api\Data\AreProductsSalableResultInterface
     */
    public function execute(string $skus, int $stockId): AreProductsSalableResultInterface;
}
