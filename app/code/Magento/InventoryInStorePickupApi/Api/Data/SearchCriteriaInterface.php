<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupApi\Api\Data;

/**
 * Search Criteria for searching Pickup Locations.
 *
 * @api
 */
interface SearchCriteriaInterface
{
    /**
     * Add radius to the Search Request.
     *
     * @param int $radius in KM.
     *
     * @return void
     */
    public function setRadius(int $radius): void;

    /**
     * @return int
     */
    public function getRadius(): int;

    /**
     * Add country to the Search Request.
     *
     * @param string $country
     *
     * @return void
     */
    public function setCountry(string $country): void;

    /**
     * Requested country
     *
     * @return string
     */
    public function getCountry(): string;

    /**
     * Add region to the Search Request.
     * Search by postcode is in priority for Offline mode.
     *
     * @param string|null $postcode
     *
     * @return void
     */
    public function setPostcode(?string $postcode): void;

    /**
     * Requested postcode
     *
     * @return string|null
     */
    public function getPostcode(): ?string;

    /**
     * Add region to the Search Request.
     * Search by region will be done only if Postcode and City are missed for Offline mode.
     *
     * @param string|null $region
     *
     * @return void
     */
    public function setRegion(?string $region): void;

    /**
     * Requested region
     *
     * @return string|null
     */
    public function getRegion(): ?string;

    /**
     * Add city to the Search Request.
     * Search by city will be done only if Postcode is missed for Offline mode.
     *
     * @param string|null $city
     *
     * @return void
     */
    public function setCity(?string $city): void;

    /**
     * Requested city
     *
     * @return string|null
     */
    public function getCity(): ?string;
}
