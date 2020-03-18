<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupApi\Api\Data\SearchRequest;

use Magento\InventoryInStorePickupApi\Api\Data\SearchRequest\FiltersExtensionInterface;

/**
 * Filter to filter by Fields.
 * Each field may be filtered with different condition type.
 * Supported condition types restricted by @see \Magento\Framework\Api\SearchCriteriaInterface
 *
 * @api
 */
interface FiltersInterface
{
    /**
     * Get Filter by Country.
     *
     * @return \Magento\InventoryInStorePickupApi\Api\Data\SearchRequest\FilterInterface|null
     */
    public function getCountry(): ?FilterInterface;

    /**
     * Get Filter by Postcode.
     *
     * @return \Magento\InventoryInStorePickupApi\Api\Data\SearchRequest\FilterInterface|null
     */
    public function getPostcode(): ?FilterInterface;

    /**
     * Get Filter by Region.
     *
     * @return \Magento\InventoryInStorePickupApi\Api\Data\SearchRequest\FilterInterface|null
     */
    public function getRegion(): ?FilterInterface;

    /**
     * Get Filter by Region Id.
     *
     * @return \Magento\InventoryInStorePickupApi\Api\Data\SearchRequest\FilterInterface|null
     */
    public function getRegionId(): ?FilterInterface;

    /**
     * Get Filter by City.
     *
     * @return \Magento\InventoryInStorePickupApi\Api\Data\SearchRequest\FilterInterface|null
     */
    public function getCity(): ?FilterInterface;

    /**
     * Get Filter by Street.
     *
     * @return \Magento\InventoryInStorePickupApi\Api\Data\SearchRequest\FilterInterface|null
     */
    public function getStreet(): ?FilterInterface;

    /**
     * Get Filter by Name.
     *
     * @return \Magento\InventoryInStorePickupApi\Api\Data\SearchRequest\FilterInterface|null
     */
    public function getName(): ?FilterInterface;

    /**
     * Get Filter by Pickup Location Code.
     *
     * @return \Magento\InventoryInStorePickupApi\Api\Data\SearchRequest\FilterInterface|null
     */
    public function getPickupLocationCode(): ?FilterInterface;
}
