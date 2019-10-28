<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model;

use InvalidArgumentException;
use Magento\Framework\Api\SortOrder;
use Magento\InventoryInStorePickup\Model\SearchRequest\Builder\FilterSetBuilder;
use Magento\InventoryInStorePickup\Model\SearchRequest\Builder\DistanceFilterBuilder;
use Magento\InventoryInStorePickupApi\Api\Data\SearchRequest\DistanceFilterExtensionInterface;
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
    private const FILTER_SET = 'filterSet';
    private const DISTANCE_FILTER = 'distanceFilter';
    private const SORT_ORDERS = 'sort';
    private const PAGE_SIZE = 'pageSize';
    private const CURRENT_PAGE = 'currentPage';
    private const SCOPE_CODE = 'scopeCode';
    private const SCOPE_TYPE = 'scopeType';
    private const SEARCH_REQUEST_EXTENSION = 'searchRequestExtension';

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
     * @var FilterSetBuilder
     */
    private $filterSetBuilder;

    /**
     * @var DistanceFilterBuilder
     */
    private $distanceFilterBuilder;

    /**
     * @param FilterSetBuilder $filterSetBuilderFactory
     * @param DistanceFilterBuilder $distanceFilterBuilderFactory
     * @param SearchRequestInterfaceFactory $searchRequestFactory
     */
    public function __construct(
        FilterSetBuilder $filterSetBuilderFactory,
        DistanceFilterBuilder $distanceFilterBuilderFactory,
        SearchRequestInterfaceFactory $searchRequestFactory
    ) {
        $this->searchRequestFactory = $searchRequestFactory;
        $this->filterSetBuilder = $filterSetBuilderFactory;
        $this->distanceFilterBuilder = $distanceFilterBuilderFactory;
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
        $this->data[self::FILTER_SET] = $this->filterSetBuilder->create();
        $this->data[self::DISTANCE_FILTER] = $this->distanceFilterBuilder->create();
    }

    /**
     * @inheritdoc
     */
    public function setStreetFilter(string $street, ?string $condition = null): SearchRequestBuilderInterface
    {
        $this->filterSetBuilder->setStreetFilter($street, $condition);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setPostcodeFilter(string $postcode, ?string $condition = null): SearchRequestBuilderInterface
    {
        $this->filterSetBuilder->setPostcodeFilter($postcode, $condition);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setCityFilter(string $city, ?string $condition = null): SearchRequestBuilderInterface
    {
        $this->filterSetBuilder->setCityFilter($city, $condition);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setRegionIdFilter(string $regionId, ?string $condition = null): SearchRequestBuilderInterface
    {
        $this->filterSetBuilder->setRegionIdFilter($regionId, $condition);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setRegionFilter(string $region, ?string $condition = null): SearchRequestBuilderInterface
    {
        $this->filterSetBuilder->setRegionFilter($region, $condition);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setCountryFilter(string $country, ?string $condition): SearchRequestBuilderInterface
    {
        $this->filterSetBuilder->setCountryFilter($country, $condition);

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
    public function setDistanceFilterExtension(
        DistanceFilterExtensionInterface $extension
    ): SearchRequestBuilderInterface {
        $this->distanceFilterBuilder->setExtensionAttributes($extension);

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
        $this->filterSetBuilder->setPickupLocationCodeFilter($code, $condition);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setNameFilter(string $name, ?string $condition = null): SearchRequestBuilderInterface
    {
        $this->filterSetBuilder->setNameFilter($name, $condition);

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
