<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model\SearchRequest\Builder;

use Magento\InventoryInStorePickupApi\Api\Data\SearchRequest\DistanceFilterInterface;
use Magento\InventoryInStorePickupApi\Api\Data\SearchRequest\DistanceFilterInterfaceFactory;
use Magento\InventoryInStorePickupApi\Model\SearchRequest\DistanceFilterBuilderInterface;

/**
 * @inheritdoc
 */
class DistanceFilterBuilder implements DistanceFilterBuilderInterface
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
     * @inheritdoc
     */
    public function create(): ?DistanceFilterInterface
    {
        $data = $this->data;
        $this->data = [];

        return empty($data) ? null : $this->distanceFilterFactory->create($data);
    }

    /**
     * @inheritdoc
     */
    public function setRadius(int $radius): DistanceFilterBuilderInterface
    {
        $this->data[self::RADIUS] = $radius;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setPostcode(string $postcode): DistanceFilterBuilderInterface
    {
        $this->data[self::POSTCODE] = $postcode;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setCity(string $city): DistanceFilterBuilderInterface
    {
        $this->data[self::CITY] = $city;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setRegion(string $region): DistanceFilterBuilderInterface
    {
        $this->data[self::REGION] = $region;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setCountry(string $country): DistanceFilterBuilderInterface
    {
        $this->data[self::COUNTRY] = $country;
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
