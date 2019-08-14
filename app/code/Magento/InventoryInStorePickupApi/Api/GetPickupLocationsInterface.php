<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupApi\Api;

use Magento\InventoryInStorePickupApi\Api\Data\SearchRequestInterface;
use Magento\InventoryInStorePickupApi\Api\Data\SearchResultInterface;

/**
 * Get Pickup Locations filtered by provided Search Request.
 *
 * @api
 */
interface GetPickupLocationsInterface
{
    /**
     * Get Pickup Locations for requested Sales Channel, ordered by corresponded Source priority.
     *
     * @param \Magento\InventoryInStorePickupApi\Api\Data\SearchRequestInterface $searchRequest
     * @return \Magento\InventoryInStorePickupApi\Api\Data\SearchResultInterface
     */
    public function execute(SearchRequestInterface $searchRequest): SearchResultInterface;
}
