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
interface SearchCriteriaInterface extends \Magento\Framework\Api\SearchCriteriaInterface
{
    /**
     * Add radius to the Search Request.
     *
     * @param int $radius in KM.
     *
     * @return SearchCriteriaInterface
     */
    public function setRadius(int $radius): self;

    /**
     * @return int
     */
    public function getRadius(): int;

    /**
     * Add country to the Search Request.
     *
     * @param string $country
     *
     * @return SearchCriteriaInterface
     */
    public function setCountry(string $country): self;

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
     * @return SearchCriteriaInterface
     */
    public function setPostcode(?string $postcode): self;

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
     * @return SearchCriteriaInterface
     */
    public function setRegion(?string $region): self;

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
     * @return SearchCriteriaInterface
     */
    public function setCity(?string $city): self;

    /**
     * Requested city
     *
     * @return string|null
     */
    public function getCity(): ?string;
}
