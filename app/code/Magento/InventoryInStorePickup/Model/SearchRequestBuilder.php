<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model;

use Magento\Framework\Api\SortOrder;
use Magento\InventoryInStorePickupApi\Api\Data\SearchRequest\AddressFilterInterfaceFactory;
use Magento\InventoryInStorePickupApi\Api\Data\SearchRequest\DistanceFilterInterfaceFactory;
use Magento\InventoryInStorePickupApi\Api\Data\SearchRequest\FilterInterface;
use Magento\InventoryInStorePickupApi\Api\Data\SearchRequest\FilterInterfaceFactory;
use Magento\InventoryInStorePickupApi\Api\Data\SearchRequestExtensionInterface;
use Magento\InventoryInStorePickupApi\Api\Data\SearchRequestInterface;
use Magento\InventoryInStorePickupApi\Api\Data\SearchRequestInterfaceFactory;
use Magento\InventoryInStorePickupApi\Model\SearchRequestBuilderInterface;

/**
 * @inheritdoc
 */
class SearchRequestBuilder implements SearchRequestBuilderInterface
{
    /**
     * Search Request Fields.
     */
    private const ADDRESS_FILTER = 'addressFilter';
    private const DISTANCE_FILTER = 'distanceFilter';
    private const NAME_FILTER = 'nameFilter';
    private const PICKUP_LOCATION_CODE_FILTER = 'pickupLocationCodeFilter';
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
     * @inheritdoc
     */
    public function create(): SearchRequestInterface
    {
        $this->buildDistanceFilter();
        $this->buildAddressFilter();
        $searchRequest = $this->searchRequestFactory->create($this->data);

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
        if (!empty($addressFilterData)) {
            $this->data[self::ADDRESS_FILTER] = $this->addressFilterFactory->create($addressFilterData);
        } else {
            unset($this->data[self::ADDRESS_FILTER]);
        }
    }

    /**
     * @inheritdoc
     */
    public function setStreetFilter(string $street, ?string $condition = null): SearchRequestBuilderInterface
    {
        $this->data[self::ADDRESS_FILTER][self::STREET_FILTER] = $this->createFilter($street, $condition);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setPostcodeFilter(string $postcode, ?string $condition = null): SearchRequestBuilderInterface
    {
        $this->data[self::ADDRESS_FILTER][self::POSTCODE_FILTER] = $this->createFilter($postcode, $condition);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setAddressCityFilter(string $city, ?string $condition = null): SearchRequestBuilderInterface
    {
        $this->data[self::ADDRESS_FILTER][self::CITY_FILTER] = $this->createFilter($city, $condition);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setAddressRegionIdFilter(string $regionId, ?string $condition = null): SearchRequestBuilderInterface
    {
        $this->data[self::ADDRESS_FILTER][self::REGION_ID_FILTER] = $this->createFilter($regionId, $condition);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setAddressRegionFilter(string $region, ?string $condition = null): SearchRequestBuilderInterface
    {
        $this->data[self::ADDRESS_FILTER][self::REGION_FILTER] = $this->createFilter($region, $condition);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setAddressCountryFilter(string $country, ?string $condition): SearchRequestBuilderInterface
    {
        $this->data[self::ADDRESS_FILTER][self::COUNTRY_FILTER] = $this->createFilter($country, $condition);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setDistanceFilterRadius(int $radius): SearchRequestBuilderInterface
    {
        $this->data[self::DISTANCE_FILTER][self::RADIUS] = $radius;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setDistanceFilterPostcode(string $postcode): SearchRequestBuilderInterface
    {
        $this->data[self::DISTANCE_FILTER][self::POSTCODE] = $postcode;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setDistanceFilterCity(string $city): SearchRequestBuilderInterface
    {
        $this->data[self::DISTANCE_FILTER][self::CITY] = $city;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setDistanceFilterRegion(string $region): SearchRequestBuilderInterface
    {
        $this->data[self::DISTANCE_FILTER][self::REGION] = $region;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setDistanceFilterCountry(string $country): SearchRequestBuilderInterface
    {
        $this->data[self::DISTANCE_FILTER][self::COUNTRY] = $country;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setSearchRequestExtension(SearchRequestExtensionInterface $extension): SearchRequestBuilderInterface
    {
        $this->data[self::SEARCH_REQUEST_EXTENSION] = $extension;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setSortOrders(array $sortOrders): SearchRequestBuilderInterface
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
     * @inheritdoc
     */
    public function setScopeCode(string $scopeCode): SearchRequestBuilderInterface
    {
        $this->data[self::SCOPE_CODE] = $scopeCode;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setScopeType(string $scopeType): SearchRequestBuilderInterface
    {
        $this->data[self::SCOPE_TYPE] = $scopeType;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setPickupLocationCodeFilter(string $code, ?string $condition = null): SearchRequestBuilderInterface
    {
        $this->data[self::PICKUP_LOCATION_CODE_FILTER] = $this->createFilter($code, $condition);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setNameFilter(string $name, ?string $condition = null): SearchRequestBuilderInterface
    {
        $this->data[self::NAME_FILTER] = $this->createFilter($name, $condition);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setCurrentPage(int $page): SearchRequestBuilderInterface
    {
        $this->data[self::CURRENT_PAGE] = $page;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setPageSize(int $pageSize): SearchRequestBuilderInterface
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
