<?php
/**
 * Created by PhpStorm.
 * User: al.kravchuk
 * Date: 20.08.19
 * Time: 20:58
 */

namespace Magento\InventoryInStorePickupApi\Model;

use Magento\Framework\Api\SortOrder;
use Magento\InventoryInStorePickupApi\Api\Data\SearchRequestExtensionInterface;
use Magento\InventoryInStorePickupApi\Api\Data\SearchRequestInterface;

/**
 * Search Request Builder.
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
     * Set Street for filter by address.
     *
     * @param string $street
     * @param string|null $condition
     *
     * @return SearchRequestBuilderInterface
     */
    public function setStreetFilter(string $street, ?string $condition = null): self;

    /**
     * Set Postcode for filter by address.
     *
     * @param string $postcode
     * @param string|null $condition
     *
     * @return SearchRequestBuilderInterface
     */
    public function setPostcodeFilter(string $postcode, ?string $condition = null): self;

    /**
     * Set City for filter by address.
     *
     * @param string $city
     * @param string|null $condition
     *
     * @return SearchRequestBuilderInterface
     */
    public function setAddressCityFilter(string $city, ?string $condition = null): self;

    /**
     * Set Region Id for filter by address.
     *
     * @param string $regionId
     * @param string|null $condition
     *
     * @return SearchRequestBuilderInterface
     */
    public function setAddressRegionIdFilter(string $regionId, ?string $condition = null): self;

    /**
     * Set Region for filter by address.
     *
     * @param string $region
     * @param string|null $condition
     *
     * @return SearchRequestBuilderInterface
     */
    public function setAddressRegionFilter(string $region, ?string $condition = null): self;

    /**
     * Set country for filter by address.
     *
     * @param string $country
     * @param string|null $condition
     *
     * @return SearchRequestBuilderInterface
     */
    public function setAddressCountryFilter(string $country, ?string $condition): self;

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
    public function setDistanceFilterCountry(
        string $country
    ): self;

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
     * Add filter by Pickup Location Code.
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
     * Set current page. Not required.
     *
     * @param int $page
     *
     * @return SearchRequestBuilderInterface
     */
    public function setCurrentPage(int $page): self;

    /**
     * Set page size. Not required.
     *
     * @param int $pageSize
     *
     * @return SearchRequestBuilderInterface
     */
    public function setPageSize(int $pageSize): self;
}
