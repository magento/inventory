<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */

declare(strict_types=1);

namespace Magento\InventoryReservationsApi\Model;

/**
 * Returns the total quantity of reservations for each SKU in the given stock.
 */
interface GetReservationsQuantityBySkuListInterface
{
    /**
     * Returns the total quantity of reservations for each SKU in the given stock.
     *
     * @param string[] $skus
     * @param int $stockId
     * @return array<string, float> An associative array where the keys are SKUs and the values are
     * the total quantity of reservations for that SKU in the given stock.
     */
    public function execute(array $skus, int $stockId): array;
}
