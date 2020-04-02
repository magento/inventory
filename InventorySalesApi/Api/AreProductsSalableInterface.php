<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySalesApi\Api;

/**
 * Service which detects whether products are salable for given stock (stock data + reservations).
 *
 * @api
 */
interface AreProductsSalableInterface
{
    /**
     * Get products salable status for given SKUs and given Stock.
     *
     * @param string[] $skus
     * @param int $stockId
     * @return \Magento\InventorySalesApi\Api\Data\IsProductSalableResultInterface[]
     */
    public function execute(array $skus, int $stockId): array;
}
