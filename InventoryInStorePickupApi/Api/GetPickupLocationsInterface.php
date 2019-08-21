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
 * Pickup Location entities are Immutable object and can not be changed after creation.
 * All modification of Pickup Location must be done through @see \Magento\InventoryApi\Api\SourceRepositoryInterface
 *
 * @api
 */
interface GetPickupLocationsInterface
{
    /**
     * Get Pickup Locations according to the results of filtration by Search Request.
     *
     * @param \Magento\InventoryInStorePickupApi\Api\Data\SearchRequestInterface $searchRequest
     * @return \Magento\InventoryInStorePickupApi\Api\Data\SearchResultInterface
     */
    public function execute(SearchRequestInterface $searchRequest): SearchResultInterface;
}
