<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupApi\Model;

use InvalidArgumentException;
use Magento\Framework\Api\SortOrder;
use Magento\InventoryInStorePickupApi\Api\Data\SearchRequest\AddressFilterInterfaceFactory;
use Magento\InventoryInStorePickupApi\Api\Data\SearchRequest\DistanceFilterInterfaceFactory;
use Magento\InventoryInStorePickupApi\Api\Data\SearchRequest\FilterInterface;
use Magento\InventoryInStorePickupApi\Api\Data\SearchRequest\FilterInterfaceFactory;
use Magento\InventoryInStorePickupApi\Api\Data\SearchRequestExtensionInterface;
use Magento\InventoryInStorePickupApi\Api\Data\SearchRequestInterface;
use Magento\InventoryInStorePickupApi\Api\Data\SearchRequestInterfaceFactory;
use TypeError;

/**
 * Search Request Builder.
 */
class SearchRequestBuilder
{
    /**
     * Search Request Fields.
     */
    private const ADDRESS_FILTER = 'addressFilter';
    private const DISTANCE_FILTER = 'distanceFilter';
    private const NAME_FILTER = 'nameFilter';
    private const PICKUP_LOCATION_CODE_FILTER = 'pickupLocationFilter';
    private const SORT_ORDERS = 'sortOrders';
    private const PAGE_SIZE = 'pageSize';
    private const CURRENT_PAGE = 'currentPage';
    private const SCOPE_CODE = 'scopeCode';
    private const SCOPE_TYPE = 'scopeType';
    private const SEARCH_REQUEST_EXTENSION = 'searchRequestExtension';

    /**
     * Distance Filter fields.
     */
    private const RADIUS = 'radius';
    private const COUNTRY = 'country';
    private const CITY = 'city';
    private const REGION = 'region';
    private const POSTCODE = 'postcode';

    /**
     * Address Filter fields.
     */
    private const COUNTRY_FILTER = 'countryFilter';
    private const POSTCODE_FILTER = 'postcodeFilter';
    private const REGION_FILTER = 'regionFilter';
    private const REGION_ID_FILTER = 'regionIdFilter';
    private const CITY_FILTER = 'cityFilter';
    private const STREET_FILTER = 'streetFilter';

    /**
     * Filter fields.
     */
    private const FIELD_VALUE = 'value';
    private const FIELD_CONDITION_TYPE = 'conditionType';

    /**
     * Builder data
     *
     * @var array
     */
    private $data = [
        self::ADDRESS_FILTER => [],
        self::DISTANCE_FILTER => []
    ];

    /**
     * @var FilterInterfaceFactory
     */
    private $filterFactory;

    /**
     * @var DistanceFilterInterfaceFactory
     */
    private $distanceFilterFactory;

    /**
     * @var AddressFilterInterfaceFactory
     */
    private $addressFilterFactory;

    /**
     * @var SearchRequestInterfaceFactory
     */
    private $searchRequestFactory;

    /**
     * @param FilterInterfaceFactory $filterFactory
     * @param DistanceFilterInterfaceFactory $distanceFilterFactory
     * @param AddressFilterInterfaceFactory $addressFilterFactory
     * @param SearchRequestInterfaceFactory $searchRequestFactory
     */
    public function __construct(
        FilterInterfaceFactory $filterFactory,
        DistanceFilterInterfaceFactory $distanceFilterFactory,
        AddressFilterInterfaceFactory $addressFilterFactory,
        SearchRequestInterfaceFactory $searchRequestFactory
    ) {
        $this->filterFactory = $filterFactory;
        $this->distanceFilterFactory = $distanceFilterFactory;
        $this->addressFilterFactory = $addressFilterFactory;
        $this->searchRequestFactory = $searchRequestFactory;
    }

    /**
     * Build Search Request object.
     *
     * @return SearchRequestInterface
     */
    public function create(): SearchRequestInterface
    {
        try {
            $this->buildDistanceFilter();
        } catch (TypeError $error) {
            throw new InvalidArgumentException('Invalid DistanceFilter arguments given.', 0, $error);
        }

        try {
            $this->buildAddressFilter();
        } catch (TypeError $error) {
            throw new InvalidArgumentException('Invalid AddressFilter arguments given.', 0, $error);
        }

        try {
            $searchRequest = $this->searchRequestFactory->create($this->data);
        } catch (TypeError $error) {
            throw new InvalidArgumentException('Invalid SearchRequest arguments given.', 0, $error);
        }

        $this->reset();

        return $searchRequest;
    }

    /**
     * Reset builder data.
     *
     * @return void
     */
    private function reset(): void
    {
        $this->data = [
            self::ADDRESS_FILTER => [],
            self::DISTANCE_FILTER => []
        ];
    }

    /**
     * Build Distance Filter.
     *
     * @return void
     */
    private function buildDistanceFilter(): void
    {
        $distanceFilterData = $this->data[self::DISTANCE_FILTER];
        if (!empty($distanceFilterData)) {
            $this->data[self::DISTANCE_FILTER] = $this->distanceFilterFactory->create($distanceFilterData);
        } else {
            unset($this->data[self::DISTANCE_FILTER]);
        }
    }

    /**
     * Build Address Filter.
     *
     * @return void
     */
    private function buildAddressFilter(): void
    {
        $addressFilterData = $this->data[self::ADDRESS_FILTER];
        if (!empty($distanceFilterData)) {
            $this->data[self::ADDRESS_FILTER] = $this->addressFilterFactory->create($addressFilterData);
        } else {
            unset($this->data[self::ADDRESS_FILTER]);
        }
    }

    /**
     * Set Street for filter by address.
     *
     * @param string $street
     * @param string|null $condition
     *
     * @return SearchRequestBuilder
     */
    public function setStreetFilter(string $street, ?string $condition = null): self
    {
        $this->data[self::ADDRESS_FILTER][self::STREET_FILTER] = $this->createFilter($street, $condition);

        return $this;
    }

    /**
     * Set Postcode for filter by address.
     *
     * @param string $postcode
     * @param string|null $condition
     *
     * @return SearchRequestBuilder
     */
    public function setPostcodeFilter(string $postcode, ?string $condition = null): self
    {
        $this->data[self::ADDRESS_FILTER][self::POSTCODE_FILTER] = $this->createFilter($postcode, $condition);

        return $this;
    }

    /**
     * Set City for filter by address.
     *
     * @param string $city
     * @param string|null $condition
     *
     * @return SearchRequestBuilder
     */
    public function setAddressCityFilter(string $city, ?string $condition = null): self
    {
        $this->data[self::ADDRESS_FILTER][self::CITY_FILTER] = $this->createFilter($city, $condition);

        return $this;
    }

    /**
     * Set Region Id for filter by address.
     *
     * @param string $regionId
     * @param string|null $condition
     *
     * @return SearchRequestBuilder
     */
    public function setAddressRegionIdFilter(string $regionId, ?string $condition = null): self
    {
        $this->data[self::ADDRESS_FILTER][self::REGION_ID_FILTER] = $this->createFilter($regionId, $condition);

        return $this;
    }

    /**
     * Set Region for filter by address.
     *
     * @param string $region
     * @param string|null $condition
     *
     * @return SearchRequestBuilder
     */
    public function setAddressRegionFilter(string $region, ?string $condition = null): self
    {
        $this->data[self::ADDRESS_FILTER][self::REGION_FILTER] = $this->createFilter($region, $condition);

        return $this;
    }

    /**
     * Set country for filter by address.
     *
     * @param string $country
     * @param string|null $condition
     *
     * @return SearchRequestBuilder
     */
    public function setAddressCountryFilter(string $country, ?string $condition): self
    {
        $this->data[self::ADDRESS_FILTER][self::COUNTRY_FILTER] = $this->createFilter($country, $condition);

        return $this;
    }

    /**
     * Set Radius for Distance Filter.
     *
     * @param int $radius
     *
     * @return SearchRequestBuilder
     */
    public function setDistanceFilterRadius(int $radius): self
    {
        $this->data[self::DISTANCE_FILTER][self::RADIUS] = $radius;

        return $this;
    }

    /**
     * Set Postcode for Distance Filter.
     *
     * @param string $postcode
     *
     * @return SearchRequestBuilder
     */
    public function setDistanceFilterPostcode(string $postcode): self
    {
        $this->data[self::DISTANCE_FILTER][self::POSTCODE] = $postcode;

        return $this;
    }

    /**
     * Set City for Distance filter.
     *
     * @param string $city
     *
     * @return SearchRequestBuilder
     */
    public function setDistanceFilterCity(string $city): self
    {
        $this->data[self::DISTANCE_FILTER][self::CITY] = $city;

        return $this;
    }

    /**
     * Set Region for Distance filter.
     *
     * @param string $region
     *
     * @return SearchRequestBuilder
     */
    public function setDistanceFilterRegion(string $region): self
    {
        $this->data[self::DISTANCE_FILTER][self::REGION] = $region;

        return $this;
    }

    /**
     * Set Country for Distance filter.
     *
     * @param string $country
     *
     * @return SearchRequestBuilder
     */
    public function setDistanceFilterCountry(string $country): self
    {
        $this->data[self::DISTANCE_FILTER][self::COUNTRY] = $country;

        return $this;
    }

    /**
     * Set Search Request Extension.
     *
     * @param SearchRequestExtensionInterface $extension
     *
     * @return SearchRequestBuilder
     */
    public function setSearchRequestExtension(SearchRequestExtensionInterface $extension): self
    {
        $this->data[self::SEARCH_REQUEST_EXTENSION] = $extension;

        return $this;
    }

    /**
     * Set Sort Orders.
     *
     * @param SortOrder[] $sortOrders
     *
     * @return SearchRequestBuilder
     */
    public function setSortOrders(array $sortOrders): self
    {
        $this->validateSortOrder($sortOrders);
        $this->data[self::SORT_ORDERS] = $sortOrders;

        return $this;
    }

    /**
     * Validate Sort Orders input.
     *
     * @param array|null $sortOrders
     */
    private function validateSortOrder(array $sortOrders): void
    {
        foreach ($sortOrders as $sortOrder) {
            if (!$sortOrder instanceof SortOrder) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'Invalid Sort Order provided for %s. Sort Order must implement %s.',
                        self::class,
                        SortOrder::class
                    )
                );
            }
        }
    }

    /**
     * Set scope code.
     *
     * @param string $scopeCode
     *
     * @return SearchRequestBuilder
     */
    public function setScopeCode(string $scopeCode): self
    {
        $this->data[self::SCOPE_CODE] = $scopeCode;

        return $this;
    }

    /**
     * Set scope type.
     *
     * @param string $scopeType
     *
     * @return SearchRequestBuilder
     */
    public function setScopeType(string $scopeType): self
    {
        $this->data[self::SCOPE_TYPE] = $scopeType;

        return $this;
    }

    /**
     * Add filter by Pickup Location Code.
     *
     * @param string $code
     * @param string|null $condition
     *
     * @return SearchRequestBuilder
     */
    public function setPickupLocationCodeFilter(string $code, ?string $condition = null): self
    {
        $this->data[self::PICKUP_LOCATION_CODE_FILTER] = $this->createFilter($code, $condition);

        return $this;
    }

    /**
     * Set Filter by Pickup Location Name.
     *
     * @param string $name
     * @param string|null $condition
     *
     * @return SearchRequestBuilder
     */
    public function setNameFilter(string $name, ?string $condition = null): self
    {
        $this->data[self::NAME_FILTER] = $this->createFilter($name, $condition);

        return $this;
    }

    /**
     * Set current page. Not required.
     *
     * @param int $page
     *
     * @return SearchRequestBuilder
     */
    public function setCurrentPage(int $page): self
    {
        $this->data[self::CURRENT_PAGE] = $page;

        return $this;
    }

    /**
     * Set page size. Not required.
     *
     * @param int $pageSize
     *
     * @return SearchRequestBuilder
     */
    public function setPageSize(int $pageSize): self
    {
        $this->data[self::PAGE_SIZE] = $pageSize;

        return $this;
    }

    /**
     * Create filter.
     *
     * @param string $value
     * @param string|null $condition
     *
     * @return FilterInterface
     */
    private function createFilter(string $value, ?string $condition = null): FilterInterface
    {
        $data[self::FIELD_VALUE] = $value;
        if ($condition !== null) {
            $data[self::FIELD_CONDITION_TYPE] = $condition;
        }

        return $this->filterFactory->create($data);
    }
}
