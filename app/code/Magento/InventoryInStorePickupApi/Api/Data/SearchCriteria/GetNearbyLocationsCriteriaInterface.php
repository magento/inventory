<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupApi\Api\Data\SearchCriteria;

use Magento\InventoryInStorePickupApi\Api\Data\SearchCriteriaInterface;

/**
 * Search Criteria for searching Pickup Locations.
 *
 * @api
 */
interface GetNearbyLocationsCriteriaInterface extends SearchCriteriaInterface
{
    /**
     * @return int
     */
    public function getRadius(): int;

    /**
     * Requested country
     *
     * @return string
     */
    public function getCountry(): string;

    /**
     * Requested postcode
     *
     * @return string|null
     */
    public function getPostcode(): ?string;

    /**
     * Requested region
     *
     * @return string|null
     */
    public function getRegion(): ?string;

    /**
     * Requested city
     *
     * @return string|null
     */
    public function getCity(): ?string;
}
