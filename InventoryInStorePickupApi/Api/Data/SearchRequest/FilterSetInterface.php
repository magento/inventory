<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupApi\Api\Data\SearchRequest;

/**
 * Filter to filter by Fields.
 * Each field may be filtered with different condition type.
 * Supported condition types restricted by @see \Magento\Framework\Api\SearchCriteriaInterface
 *
 * @api
 */
interface FilterSetInterface
{
    /**
     * Get Filter by Country.
     *
     * @return \Magento\InventoryInStorePickupApi\Api\Data\SearchRequest\FilterInterface|null
     */
    public function getCountryFilter(): ?FilterInterface;

    /**
     * Get Filter by Postcode.
     *
     * @return \Magento\InventoryInStorePickupApi\Api\Data\SearchRequest\FilterInterface|null
     */
    public function getPostcodeFilter(): ?FilterInterface;

    /**
     * Get Filter by Region.
     *
     * @return \Magento\InventoryInStorePickupApi\Api\Data\SearchRequest\FilterInterface|null
     */
    public function getRegionFilter(): ?FilterInterface;

    /**
     * Get Filter by Region Id.
     *
     * @return \Magento\InventoryInStorePickupApi\Api\Data\SearchRequest\FilterInterface|null
     */
    public function getRegionIdFilter(): ?FilterInterface;

    /**
     * Get Filter by City.
     *
     * @return \Magento\InventoryInStorePickupApi\Api\Data\SearchRequest\FilterInterface|null
     */
    public function getCityFilter(): ?FilterInterface;

    /**
     * Get Filter by Street.
     *
     * @return \Magento\InventoryInStorePickupApi\Api\Data\SearchRequest\FilterInterface|null
     */
    public function getStreetFilter(): ?FilterInterface;

    /**
     * Get Filter by Name.
     *
     * @return \Magento\InventoryInStorePickupApi\Api\Data\SearchRequest\FilterInterface|null
     */
    public function getNameFilter(): ?FilterInterface;

    /**
     * Get Filter by Pickup Location Code.
     *
     * @return \Magento\InventoryInStorePickupApi\Api\Data\SearchRequest\FilterInterface|null
     */
    public function getPickupLocationCodeFilter(): ?FilterInterface;
}
