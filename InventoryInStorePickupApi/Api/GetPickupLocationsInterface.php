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
 * Endpoint used to filter Pickup Locations by different parameters:
 * - by address fields @see \Magento\InventoryInStorePickupApi\Api\Data\SearchRequest\AddressFilterInterface
 * - by distance to the address @see \Magento\InventoryInStorePickupApi\Api\Data\SearchRequest\DistanceFilterInterface
 * - by Pickup Location Code(s) and Name(s) @see \Magento\InventoryInStorePickupApi\Api\Data\SearchRequestInterface
 * Also, endpoint support paging and sort orders.
 *
 * Pickup Location entities are Immutable object and can not be changed after creation.
 * All modification of Pickup Location must be done throw @see \Magento\InventoryApi\Api\SourceRepositoryInterface
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
