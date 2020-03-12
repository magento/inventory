<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model\SearchRequest;

use Magento\InventoryInStorePickupApi\Api\Data\SearchRequest\FiltersInterface;
use Magento\InventoryInStorePickupApi\Api\Data\SearchRequest\FilterInterface;

/**
 * @inheritdoc
 */
class Filters implements FiltersInterface
{
    /**
     * @var FilterInterface|null
     */
    private $country;

    /**
     * @var FilterInterface|null
     */
    private $postcode;

    /**
     * @var FilterInterface|null
     */
    private $region;

    /**
     * @var FilterInterface|null
     */
    private $regionId;

    /**
     * @var FilterInterface|null
     */
    private $city;

    /**
     * @var FilterInterface|null
     */
    private $street;

    /**
     * @var FilterInterface|null
     */
    private $name;

    /**
     * @var FilterInterface|null
     */
    private $pickupLocationCode;

    /**
     * @param FilterInterface|null $name
     * @param FilterInterface|null $pickupLocationCode
     * @param FilterInterface|null $country
     * @param FilterInterface|null $postcode
     * @param FilterInterface|null $region
     * @param FilterInterface|null $regionId
     * @param FilterInterface|null $city
     * @param FilterInterface|null $street
     */
    public function __construct(
        ?FilterInterface $name = null,
        ?FilterInterface $pickupLocationCode = null,
        ?FilterInterface $country = null,
        ?FilterInterface $postcode = null,
        ?FilterInterface $region = null,
        ?FilterInterface $regionId = null,
        ?FilterInterface $city = null,
        ?FilterInterface $street = null
    ) {
        $this->country = $country;
        $this->postcode = $postcode;
        $this->region = $region;
        $this->regionId = $regionId;
        $this->city = $city;
        $this->street = $street;
        $this->name = $name;
        $this->pickupLocationCode = $pickupLocationCode;
    }

    /**
     * @inheritdoc
     */
    public function getCountry(): ?FilterInterface
    {
        return $this->country;
    }

    /**
     * @inheritdoc
     */
    public function getPostcode(): ?FilterInterface
    {
        return $this->postcode;
    }

    /**
     * @inheritdoc
     */
    public function getRegion(): ?FilterInterface
    {
        return $this->region;
    }

    /**
     * @inheritdoc
     */
    public function getRegionId(): ?FilterInterface
    {
        return $this->regionId;
    }

    /**
     * @inheritdoc
     */
    public function getCity(): ?FilterInterface
    {
        return $this->city;
    }

    /**
     * @inheritdoc
     */
    public function getStreet(): ?FilterInterface
    {
        return $this->street;
    }

    /**
     * @inheritdoc
     */
    public function getName(): ?FilterInterface
    {
        return $this->name;
    }

    /**
     * @inheritdoc
     */
    public function getPickupLocationCode(): ?FilterInterface
    {
        return $this->pickupLocationCode;
    }
}
