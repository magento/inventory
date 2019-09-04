<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model\SearchRequest\Builder;

use Magento\InventoryInStorePickupApi\Api\Data\SearchRequest\AddressFilterInterface;
use Magento\InventoryInStorePickupApi\Api\Data\SearchRequest\AddressFilterInterfaceFactory;

/**
 * Address Filter Builder.
 */
class AddressFilterBuilder
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
     * @var FilterBuilderFactory
     */
    private $filterBuilderFactory;

    /**
     * @var AddressFilterInterfaceFactory
     */
    private $addressFilterFactory;

    /**
     * @param FilterBuilderFactory $filterBuilderFactory
     * @param AddressFilterInterfaceFactory $addressFilterFactory
     */
    public function __construct(
        FilterBuilderFactory $filterBuilderFactory,
        AddressFilterInterfaceFactory $addressFilterFactory
    ) {
        $this->filterBuilderFactory = $filterBuilderFactory;
        $this->addressFilterFactory = $addressFilterFactory;
    }

    /**
     * Build Address Filter.
     *
     * @return AddressFilterInterface|null
     */
    public function create(): ?AddressFilterInterface
    {
        $data = $this->data;
        $this->data = [];

        /**
         * @var string $key
         * @var FilterBuilder $value
         */
        foreach ($data as $key => $value) {
            $data[$key] = $value->create();
        }

        return empty($data) ? null : $this->addressFilterFactory->create($data);
    }

    /**
     * Set Street filter.
     *
     * @param string $street
     * @param string|null $condition
     *
     * @return self
     */
    public function setStreetFilter(string $street, ?string $condition = null): self
    {
        $filterBuilder = $this->filterBuilderFactory->create()->setValue($street)->setConditionType($condition);
        $this->data[self::STREET_FILTER] = $filterBuilder;

        return $this;
    }

    /**
     * Set Postcode filter.
     *
     * @param string $postcode
     * @param string|null $condition
     *
     * @return self
     */
    public function setPostcodeFilter(string $postcode, ?string $condition = null): self
    {
        $filter = $this->filterBuilderFactory->create()->setValue($postcode)->setConditionType($condition);
        $this->data[self::POSTCODE_FILTER] = $filter;

        return $this;
    }

    /**
     * Set City filter.
     *
     * @param string $city
     * @param string|null $condition
     *
     * @return self
     */
    public function setCityFilter(string $city, ?string $condition = null): self
    {
        $filterBuilder = $this->filterBuilderFactory->create()->setValue($city)->setConditionType($condition);
        $this->data[self::CITY_FILTER] = $filterBuilder;

        return $this;
    }

    /**
     * Set Region Id filter.
     *
     * @param string $regionId
     * @param string|null $condition
     *
     * @return self
     */
    public function setRegionIdFilter(string $regionId, ?string $condition = null): self
    {
        $filterBuilder = $this->filterBuilderFactory->create()->setValue($regionId)->setConditionType($condition);
        $this->data[self::REGION_ID_FILTER] = $filterBuilder;

        return $this;
    }

    /**
     * Set Region filter.
     *
     * @param string $region
     * @param string|null $condition
     *
     * @return self
     */
    public function setRegionFilter(string $region, ?string $condition = null): self
    {
        $filterBuilder = $this->filterBuilderFactory->create()->setValue($region)->setConditionType($condition);
        $this->data[self::REGION_FILTER] = $filterBuilder;

        return $this;
    }

    /**
     * Set Country filter.
     *
     * @param string $country
     * @param string|null $condition
     *
     * @return self
     */
    public function setCountryFilter(string $country, ?string $condition): self
    {
        $filterBuilder = $this->filterBuilderFactory->create()->setValue($country)->setConditionType($condition);
        $this->data[self::COUNTRY_FILTER] = $filterBuilder;

        return $this;
    }
}
