<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model\SearchRequest\Builder;

use Magento\InventoryInStorePickupApi\Api\Data\SearchRequest\FiltersInterface;
use Magento\InventoryInStorePickupApi\Api\Data\SearchRequest\FiltersInterfaceFactory;

/**
 * Filter Set Builder.
 */
class FiltersBuilder
{
    private const COUNTRY_FILTER = 'country';
    private const POSTCODE_FILTER = 'postcode';
    private const REGION_FILTER = 'region';
    private const REGION_ID_FILTER = 'regionId';
    private const CITY_FILTER = 'city';
    private const STREET_FILTER = 'street';
    private const NAME_FILTER = 'name';
    private const PICKUP_LOCATION_CODE_FILTER = 'pickupLocationCode';

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
     * @var FiltersInterfaceFactory
     */
    private $filtersFactory;

    /**
     * @param FilterBuilderFactory $filterBuilderFactory
     * @param FiltersInterfaceFactory $filtersFactory
     */
    public function __construct(
        FilterBuilderFactory $filterBuilderFactory,
        FiltersInterfaceFactory $filtersFactory
    ) {
        $this->filterBuilderFactory = $filterBuilderFactory;
        $this->filtersFactory = $filtersFactory;
    }

    /**
     * Build Filter Set.
     *
     * @return FiltersInterface|null
     */
    public function create(): ?FiltersInterface
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

        return empty($data) ? null : $this->filtersFactory->create($data);
    }

    /**
     * Set Name filter.
     *
     * @param string $name
     * @param string|null $condition
     *
     * @return FiltersBuilder
     */
    public function setName(string $name, ?string $condition = null): self
    {
        $filterBuilder = $this->filterBuilderFactory->create()->setValue($name)->setConditionType($condition);
        $this->data[self::NAME_FILTER] = $filterBuilder;

        return $this;
    }

    /**
     * Set Pickup Location Code filter.
     *
     * @param string $code
     * @param string|null $condition
     *
     * @return FiltersBuilder
     */
    public function setPickupLocationCode(string $code, ?string $condition = null): self
    {
        $filterBuilder = $this->filterBuilderFactory->create()->setValue($code)->setConditionType($condition);
        $this->data[self::PICKUP_LOCATION_CODE_FILTER] = $filterBuilder;

        return $this;
    }

    /**
     * Set Street filter.
     *
     * @param string $street
     * @param string|null $condition
     *
     * @return self
     */
    public function setStreet(string $street, ?string $condition = null): self
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
    public function setPostcode(string $postcode, ?string $condition = null): self
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
    public function setCity(string $city, ?string $condition = null): self
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
    public function setRegionId(string $regionId, ?string $condition = null): self
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
    public function setRegion(string $region, ?string $condition = null): self
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
    public function setCountry(string $country, ?string $condition): self
    {
        $filterBuilder = $this->filterBuilderFactory->create()->setValue($country)->setConditionType($condition);
        $this->data[self::COUNTRY_FILTER] = $filterBuilder;

        return $this;
    }
}
