<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupApi\Model;

use Magento\Framework\Api\SortOrder;
use Magento\InventoryInStorePickupApi\Api\Data\SearchRequest\DistanceFilterExtensionInterface;
use Magento\InventoryInStorePickupApi\Api\Data\SearchRequestExtensionInterface;
use Magento\InventoryInStorePickupApi\Api\Data\SearchRequestInterface;

/**
 * Search Request Builder.
 *
 * @api
 */
interface SearchRequestBuilderInterface
{
    /**
     * Build Search Request object.
     *
     * @return SearchRequestInterface
     */
    public function create(): SearchRequestInterface;

    /**
     * Set filter by Street.
     *
     * @param string $street
     * @param string|null $condition
     *
     * @return SearchRequestBuilderInterface
     */
    public function setStreetFilter(string $street, ?string $condition = null): self;

    /**
     * Set filter by Postcode.
     *
     * @param string $postcode
     * @param string|null $condition
     *
     * @return SearchRequestBuilderInterface
     */
    public function setPostcodeFilter(string $postcode, ?string $condition = null): self;

    /**
     * Set filter by City.
     *
     * @param string $city
     * @param string|null $condition
     *
     * @return SearchRequestBuilderInterface
     */
    public function setCityFilter(string $city, ?string $condition = null): self;

    /**
     * Set filter by Region Id.
     *
     * @param string $regionId
     * @param string|null $condition
     *
     * @return SearchRequestBuilderInterface
     */
    public function setRegionIdFilter(string $regionId, ?string $condition = null): self;

    /**
     * Set filter by Region.
     *
     * @param string $region
     * @param string|null $condition
     *
     * @return SearchRequestBuilderInterface
     */
    public function setRegionFilter(string $region, ?string $condition = null): self;

    /**
     * Set filter by Country Code.
     *
     * @param string $country
     * @param string|null $condition
     *
     * @return SearchRequestBuilderInterface
     */
    public function setCountryFilter(string $country, ?string $condition): self;

    /**
     * Set Radius for Distance Filter.
     *
     * @param int $radius
     *
     * @return SearchRequestBuilderInterface
     */
    public function setDistanceFilterRadius(int $radius): self;

    /**
     * Set Postcode for Distance Filter.
     *
     * @param string $postcode
     *
     * @return SearchRequestBuilderInterface
     */
    public function setDistanceFilterPostcode(string $postcode): self;

    /**
     * Set City for Distance filter.
     *
     * @param string $city
     *
     * @return SearchRequestBuilderInterface
     */
    public function setDistanceFilterCity(string $city): self;

    /**
     * Set Region for Distance filter.
     *
     * @param string $region
     *
     * @return SearchRequestBuilderInterface
     */
    public function setDistanceFilterRegion(string $region): self;

    /**
     * Set Country for Distance filter.
     *
     * @param string $country
     *
     * @return SearchRequestBuilderInterface
     */
    public function setDistanceFilterCountry(string $country): self;

    /**
     * Set Extension Attributes for Distance filter.
     *
     * @param DistanceFilterExtensionInterface $extension
     *
     * @return SearchRequestBuilderInterface
     */
    public function setDistanceFilterExtension(DistanceFilterExtensionInterface $extension): self;

    /**
     * Set Search Request Extension.
     *
     * @param SearchRequestExtensionInterface $extension
     *
     * @return SearchRequestBuilderInterface
     */
    public function setSearchRequestExtension(SearchRequestExtensionInterface $extension): self;

    /**
     * Set Sort Orders.
     *
     * @param SortOrder[] $sortOrders
     *
     * @return SearchRequestBuilderInterface
     */
    public function setSortOrders(array $sortOrders): self;

    /**
     * Set scope code.
     *
     * @param string $scopeCode
     *
     * @return SearchRequestBuilderInterface
     */
    public function setScopeCode(string $scopeCode): self;

    /**
     * Set scope type.
     *
     * @param string $scopeType
     *
     * @return SearchRequestBuilderInterface
     */
    public function setScopeType(string $scopeType): self;

    /**
     * Set filter by Pickup Location Code.
     *
     * @param string $code
     * @param string|null $condition
     *
     * @return SearchRequestBuilderInterface
     */
    public function setPickupLocationCodeFilter(string $code, ?string $condition = null): self;

    /**
     * Set Filter by Pickup Location Name.
     *
     * @param string $name
     * @param string|null $condition
     *
     * @return SearchRequestBuilderInterface
     */
    public function setNameFilter(string $name, ?string $condition = null): self;

    /**
     * Set Current Page.
     *
     * @param int $page
     *
     * @return SearchRequestBuilderInterface
     */
    public function setCurrentPage(int $page): self;

    /**
     * Set Page Size.
     *
     * @param int $pageSize
     *
     * @return SearchRequestBuilderInterface
     */
    public function setPageSize(int $pageSize): self;
}
