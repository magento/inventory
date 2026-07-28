<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */

declare(strict_types=1);

namespace Magento\InventoryReservationsApi\Model;

use Magento\InventoryApi\Model\CacheInterface;

/**
 * Get reservations quantity by SKU list and cache the result for further processing.
 */
interface GetReservationsQuantityBySkuListCacheInterface extends
    GetReservationsQuantityBySkuListInterface,
    CacheInterface
{
}
