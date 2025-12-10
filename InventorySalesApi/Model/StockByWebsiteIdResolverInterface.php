<?php
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventorySalesApi\Model;

use Magento\InventoryApi\Api\Data\StockInterface;

/**
 * Resolve Stock by Website ID
 *
 * @api
 */
interface StockByWebsiteIdResolverInterface
{
    /**
     * Resolves and retrieves the stock information for a given website ID, returning a `StockInterface` object.
     *
     * @param int $websiteId
     * @return StockInterface
     */
    public function execute(int $websiteId): StockInterface;
}
