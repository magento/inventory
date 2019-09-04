<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model;

use InvalidArgumentException;
use Magento\Framework\Api\SortOrder;
use Magento\InventoryInStorePickup\Model\SearchRequest\Builder\AddressFilterBuilder;
use Magento\InventoryInStorePickup\Model\SearchRequest\Builder\DistanceFilterBuilder;
use Magento\InventoryInStorePickup\Model\SearchRequest\Builder\FilterBuilder;
use Magento\InventoryInStorePickup\Model\SearchRequest\Builder\FilterBuilderFactory;
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

    /**
     * Builder data
     *
     * @var array
     */
    private $data = [];

    /**
     * @var SearchRequestInterfaceFactory
     */
    private $searchRequestFactory;

    /**
     * @var AddressFilterBuilder
     */
    private $addressFilterBuilder;

    /**
     * @var DistanceFilterBuilder
     */
    private $distanceFilterBuilder;

    /**
     * @var FilterBuilder
     */
    private $codeFilter;

    /**
     * @var FilterBuilder
     */
    private $nameFilter;

    /**
     * @param AddressFilterBuilder $addressFilterBuilderFactory
     * @param DistanceFilterBuilder $distanceFilterBuilderFactory
     * @param FilterBuilderFactory $filterBuilderFactory
     * @param SearchRequestInterfaceFactory $searchRequestFactory
     */
    public function __construct(
        AddressFilterBuilder $addressFilterBuilderFactory,
        DistanceFilterBuilder $distanceFilterBuilderFactory,
        FilterBuilderFactory $filterBuilderFactory,
        SearchRequestInterfaceFactory $searchRequestFactory
    ) {
        $this->searchRequestFactory = $searchRequestFactory;
        $this->addressFilterBuilder = $addressFilterBuilderFactory;
        $this->distanceFilterBuilder = $distanceFilterBuilderFactory;
        $this->codeFilter = $filterBuilderFactory->create();
        $this->nameFilter = $filterBuilderFactory->create();
    }

    /**
     * @inheritdoc
     */
    public function create(): SearchRequestInterface
    {
        $this->buildComposite();
        $data = $this->data;
        $this->data = [];

        return $this->searchRequestFactory->create($data);
    }

    /**
     * Build Distance Filter.
     *
     * @return void
     */
    private function buildComposite(): void
    {
        $this->data[self::ADDRESS_FILTER] = $this->addressFilterBuilder->create();
        $this->data[self::DISTANCE_FILTER] = $this->distanceFilterBuilder->create();
        $this->data[self::PICKUP_LOCATION_CODE_FILTER] = $this->codeFilter->create();
        $this->data[self::NAME_FILTER] = $this->nameFilter->create();
    }

    /**
     * @inheritdoc
     */
    public function setAddressStreetFilter(string $street, ?string $condition = null): SearchRequestBuilderInterface
    {
        $this->addressFilterBuilder->setStreetFilter($street, $condition);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setAddressPostcodeFilter(string $postcode, ?string $condition = null): SearchRequestBuilderInterface
    {
        $this->addressFilterBuilder->setPostcodeFilter($postcode, $condition);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setAddressCityFilter(string $city, ?string $condition = null): SearchRequestBuilderInterface
    {
        $this->addressFilterBuilder->setCityFilter($city, $condition);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setAddressRegionIdFilter(string $regionId, ?string $condition = null): SearchRequestBuilderInterface
    {
        $this->addressFilterBuilder->setRegionIdFilter($regionId, $condition);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setAddressRegionFilter(string $region, ?string $condition = null): SearchRequestBuilderInterface
    {
        $this->addressFilterBuilder->setRegionFilter($region, $condition);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setAddressCountryFilter(string $country, ?string $condition): SearchRequestBuilderInterface
    {
        $this->addressFilterBuilder->setCountryFilter($country, $condition);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setDistanceFilterRadius(int $radius): SearchRequestBuilderInterface
    {
        $this->distanceFilterBuilder->setRadius($radius);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setDistanceFilterPostcode(string $postcode): SearchRequestBuilderInterface
    {
        $this->distanceFilterBuilder->setPostcode($postcode);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setDistanceFilterCity(string $city): SearchRequestBuilderInterface
    {
        $this->distanceFilterBuilder->setCity($city);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setDistanceFilterRegion(string $region): SearchRequestBuilderInterface
    {
        $this->distanceFilterBuilder->setRegion($region);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setDistanceFilterCountry(string $country): SearchRequestBuilderInterface
    {
        $this->distanceFilterBuilder->setCountry($country);

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
                throw new InvalidArgumentException(
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
        $this->codeFilter->setValue($code)->setConditionType($condition);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setNameFilter(string $name, ?string $condition = null): SearchRequestBuilderInterface
    {
        $this->nameFilter->setValue($name)->setConditionType($condition);

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
}
