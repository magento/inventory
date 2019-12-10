<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model;

use InvalidArgumentException;
use Magento\Framework\Api\SortOrder;
use Magento\InventoryInStorePickup\Model\SearchRequest\Builder\FiltersBuilder;
use Magento\InventoryInStorePickup\Model\SearchRequest\Builder\AreaBuilder;
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
    private const FILTERS = 'filters';
    private const AREA = 'area';
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
     * @var FiltersBuilder
     */
    private $filtersBuilder;

    /**
     * @var AreaBuilder
     */
    private $areaBuilder;

    /**
     * @param FiltersBuilder $filterSetBuilderFactory
     * @param AreaBuilder $areaBuilderFactory
     * @param SearchRequestInterfaceFactory $searchRequestFactory
     */
    public function __construct(
        FiltersBuilder $filterSetBuilderFactory,
        AreaBuilder $areaBuilderFactory,
        SearchRequestInterfaceFactory $searchRequestFactory
    ) {
        $this->searchRequestFactory = $searchRequestFactory;
        $this->filtersBuilder = $filterSetBuilderFactory;
        $this->areaBuilder = $areaBuilderFactory;
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
        $this->data[self::FILTERS] = $this->filtersBuilder->create();
        $this->data[self::AREA] = $this->areaBuilder->create();
    }

    /**
     * @inheritdoc
     */
    public function setStreetFilter(string $street, ?string $condition = null): SearchRequestBuilderInterface
    {
        $this->filtersBuilder->setStreet($street, $condition);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setPostcodeFilter(string $postcode, ?string $condition = null): SearchRequestBuilderInterface
    {
        $this->filtersBuilder->setPostcode($postcode, $condition);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setCityFilter(string $city, ?string $condition = null): SearchRequestBuilderInterface
    {
        $this->filtersBuilder->setCity($city, $condition);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setRegionIdFilter(string $regionId, ?string $condition = null): SearchRequestBuilderInterface
    {
        $this->filtersBuilder->setRegionId($regionId, $condition);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setRegionFilter(string $region, ?string $condition = null): SearchRequestBuilderInterface
    {
        $this->filtersBuilder->setRegion($region, $condition);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setCountryFilter(string $country, ?string $condition): SearchRequestBuilderInterface
    {
        $this->filtersBuilder->setCountry($country, $condition);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setAreaRadius(int $radius): SearchRequestBuilderInterface
    {
        $this->areaBuilder->setRadius($radius);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setAreaSearchTerm(string $searchTerm): SearchRequestBuilderInterface
    {
        $this->areaBuilder->setSearchTerm($searchTerm);

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
        $this->filtersBuilder->setPickupLocationCode($code, $condition);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setNameFilter(string $name, ?string $condition = null): SearchRequestBuilderInterface
    {
        $this->filtersBuilder->setName($name, $condition);

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
