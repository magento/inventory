<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model\SearchRequest;

use Magento\InventoryInStorePickupApi\Api\Data\SearchRequest\FilterSetInterface;
use Magento\InventoryInStorePickupApi\Api\Data\SearchRequest\FilterInterface;

/**
 * @inheritdoc
 */
class FilterSet implements FilterSetInterface
{
    /**
     * @var FilterInterface|null
     */
    private $countryFilter;

    /**
     * @var FilterInterface|null
     */
    private $postcodeFilter;

    /**
     * @var FilterInterface|null
     */
    private $regionFilter;

    /**
     * @var FilterInterface|null
     */
    private $regionIdFilter;

    /**
     * @var FilterInterface|null
     */
    private $cityFilter;

    /**
     * @var FilterInterface|null
     */
    private $streetFilter;

    /**
     * @var FilterInterface|null
     */
    private $nameFilter;

    /**
     * @var FilterInterface|null
     */
    private $pickupLocationCodeFilter;

    /**
     * @param FilterInterface|null $nameFilter
     * @param FilterInterface|null $pickupLocationCodeFilter
     * @param FilterInterface|null $countryFilter
     * @param FilterInterface|null $postcodeFilter
     * @param FilterInterface|null $regionFilter
     * @param FilterInterface|null $regionIdFilter
     * @param FilterInterface|null $cityFilter
     * @param FilterInterface|null $streetFilter
     */
    public function __construct(
        ?FilterInterface $nameFilter = null,
        ?FilterInterface $pickupLocationCodeFilter = null,
        ?FilterInterface $countryFilter = null,
        ?FilterInterface $postcodeFilter = null,
        ?FilterInterface $regionFilter = null,
        ?FilterInterface $regionIdFilter = null,
        ?FilterInterface $cityFilter = null,
        ?FilterInterface $streetFilter = null
    ) {
        $this->countryFilter = $countryFilter;
        $this->postcodeFilter = $postcodeFilter;
        $this->regionFilter = $regionFilter;
        $this->regionIdFilter = $regionIdFilter;
        $this->cityFilter = $cityFilter;
        $this->streetFilter = $streetFilter;
        $this->nameFilter = $nameFilter;
        $this->pickupLocationCodeFilter = $pickupLocationCodeFilter;
    }

    /**
     * @inheritdoc
     */
    public function getCountryFilter(): ?FilterInterface
    {
        return $this->countryFilter;
    }

    /**
     * @inheritdoc
     */
    public function getPostcodeFilter(): ?FilterInterface
    {
        return $this->postcodeFilter;
    }

    /**
     * @inheritdoc
     */
    public function getRegionFilter(): ?FilterInterface
    {
        return $this->regionFilter;
    }

    /**
     * @inheritdoc
     */
    public function getRegionIdFilter(): ?FilterInterface
    {
        return $this->regionIdFilter;
    }

    /**
     * @inheritdoc
     */
    public function getCityFilter(): ?FilterInterface
    {
        return $this->cityFilter;
    }

    /**
     * @inheritdoc
     */
    public function getStreetFilter(): ?FilterInterface
    {
        return $this->streetFilter;
    }

    /**
     * @inheritdoc
     */
    public function getNameFilter(): ?FilterInterface
    {
        return $this->nameFilter;
    }

    /**
     * @inheritdoc
     */
    public function getPickupLocationCodeFilter(): ?FilterInterface
    {
        return $this->pickupLocationCodeFilter;
    }
}
