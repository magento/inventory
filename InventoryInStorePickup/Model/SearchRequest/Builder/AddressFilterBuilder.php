<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model\SearchRequest\Builder;

use Magento\InventoryInStorePickupApi\Api\Data\SearchRequest\AddressFilterInterface;
use Magento\InventoryInStorePickupApi\Api\Data\SearchRequest\AddressFilterInterfaceFactory;
use Magento\InventoryInStorePickupApi\Model\SearchRequest\AddressFilterBuilderInterface;
use Magento\InventoryInStorePickupApi\Model\SearchRequest\FilterBuilderInterface;
use Magento\InventoryInStorePickupApi\Model\SearchRequest\FilterBuilderInterfaceFactory;

/**
 * @inheritdoc
 */
class AddressFilterBuilder implements AddressFilterBuilderInterface
{
    private const COUNTRY_FILTER   = 'countryFilter';
    private const POSTCODE_FILTER  = 'postcodeFilter';
    private const REGION_FILTER    = 'regionFilter';
    private const REGION_ID_FILTER = 'regionIdFilter';
    private const CITY_FILTER      = 'cityFilter';
    private const STREET_FILTER    = 'streetFilter';

    /**
     * Filter data.
     *
     * @var array
     */
    private $data = [];

    /**
     * @var FilterBuilderInterfaceFactory
     */
    private $filterBuilderFactory;

    /**
     * @var AddressFilterInterfaceFactory
     */
    private $addressFilterFactory;

    /**
     * @param FilterBuilderInterfaceFactory $filterBuilderFactory
     * @param AddressFilterInterfaceFactory $addressFilterFactory
     */
    public function __construct(
        FilterBuilderInterfaceFactory $filterBuilderFactory,
        AddressFilterInterfaceFactory $addressFilterFactory
    ) {
        $this->filterBuilderFactory = $filterBuilderFactory;
        $this->addressFilterFactory = $addressFilterFactory;
    }

    /**
     * @inheritdoc
     */
    public function create(): ?AddressFilterInterface
    {
        $data = $this->data;
        $this->data = [];

        /**
         * @var string $key
         * @var FilterBuilderInterface $value
         */
        foreach ($data as $key => $value) {
            $data[$key] = $value->create();
        }

        return empty($data) ? null : $this->addressFilterFactory->create($data);
    }

    /**
     * @inheritdoc
     */
    public function setStreetFilter(string $street, ?string $condition = null): AddressFilterBuilderInterface
    {
        $filterBuilder = $this->filterBuilderFactory->create()->setValue($street)->setConditionType($condition);
        $this->data[self::STREET_FILTER] = $filterBuilder;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setPostcodeFilter(string $postcode, ?string $condition = null): AddressFilterBuilderInterface
    {
        $filter = $this->filterBuilderFactory->create()->setValue($postcode)->setConditionType($condition);
        $this->data[self::POSTCODE_FILTER] = $filter;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setCityFilter(string $city, ?string $condition = null): AddressFilterBuilderInterface
    {
        $filterBuilder = $this->filterBuilderFactory->create()->setValue($city)->setConditionType($condition);
        $this->data[self::CITY_FILTER] = $filterBuilder;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setRegionIdFilter(string $regionId, ?string $condition = null): AddressFilterBuilderInterface
    {
        $filterBuilder = $this->filterBuilderFactory->create()->setValue($regionId)->setConditionType($condition);
        $this->data[self::REGION_ID_FILTER] = $filterBuilder;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setRegionFilter(string $region, ?string $condition = null): AddressFilterBuilderInterface
    {
        $filterBuilder = $this->filterBuilderFactory->create()->setValue($region)->setConditionType($condition);
        $this->data[self::REGION_FILTER] = $filterBuilder;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setCountryFilter(string $country, ?string $condition): AddressFilterBuilderInterface
    {
        $filterBuilder = $this->filterBuilderFactory->create()->setValue($country)->setConditionType($condition);
        $this->data[self::COUNTRY_FILTER] = $filterBuilder;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getData(): array
    {
        return $this->data;
    }
}
