<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupApi\Api\Data\SearchRequest;

/**
 * Filter to filter by Address Fields.
 *
 * @api
 */
interface AddressFilterInterface
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
}
