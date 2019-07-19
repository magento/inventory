<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupApi\Api\Data\SearchCriteria;

/**
 * Search Criteria for searching Pickup Locations.
 *
 * @api
 */
interface GetNearbyLocationsCriteriaInterface
{
    /**
     * Get search radius in KM.
     *
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

    /**
     * Get page size.
     *
     * @return int|null
     */
    public function getPageSize(): ?int;

    /**
     * Get current page.
     *
     * If not specified, 1 is returned by default.
     *
     * @return int
     */
    public function getCurrentPage(): int;
}
