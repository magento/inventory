<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model\SearchRequest\Builder;

use Magento\InventoryInStorePickupApi\Api\Data\SearchRequest\DistanceFilterInterface;
use Magento\InventoryInStorePickupApi\Api\Data\SearchRequest\DistanceFilterInterfaceFactory;

/**
 * Distance Filter Builder.
 */
class DistanceFilterBuilder
{
    private const RADIUS = 'radius';
    private const COUNTRY = 'country';
    private const CITY = 'city';
    private const REGION = 'region';
    private const POSTCODE = 'postcode';

    /**
     * Filter data.
     *
     * @var array
     */
    private $data = [];

    /**
     * @var DistanceFilterInterfaceFactory
     */
    private $distanceFilterFactory;

    /**
     * @param DistanceFilterInterfaceFactory $distanceFilterFactory
     */
    public function __construct(DistanceFilterInterfaceFactory $distanceFilterFactory)
    {
        $this->distanceFilterFactory = $distanceFilterFactory;
    }

    /**
     * Build Distance Filter.
     *
     * @return DistanceFilterInterface|null
     */
    public function create(): ?DistanceFilterInterface
    {
        $data = $this->data;
        $this->data = [];

        return empty($data) ? null : $this->distanceFilterFactory->create($data);
    }

    /**
     * Set Radius for Distance Filter.
     *
     * @param int $radius
     *
     * @return self
     */
    public function setRadius(int $radius): self
    {
        $this->data[self::RADIUS] = $radius;
        return $this;
    }

    /**
     * Set Postcode for Distance Filter.
     *
     * @param string $postcode
     *
     * @return self
     */
    public function setPostcode(string $postcode): self
    {
        $this->data[self::POSTCODE] = $postcode;
        return $this;
    }

    /**
     * Set City for Distance filter.
     *
     * @param string $city
     *
     * @return self
     */
    public function setCity(string $city): self
    {
        $this->data[self::CITY] = $city;
        return $this;
    }

    /**
     * Set Region for Distance filter.
     *
     * @param string $region
     *
     * @return self
     */
    public function setRegion(string $region): self
    {
        $this->data[self::REGION] = $region;
        return $this;
    }

    /**
     * Set Country for Distance filter.
     *
     * @param string $country
     *
     * @return self
     */
    public function setCountry(string $country): self
    {
        $this->data[self::COUNTRY] = $country;
        return $this;
    }
}
