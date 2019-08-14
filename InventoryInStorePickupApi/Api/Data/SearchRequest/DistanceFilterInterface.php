<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupApi\Api\Data\SearchRequest;

/**
 * Filter by Distance to the Address.
 *
 * @api
 */
interface DistanceFilterInterface
{
    public const DISTANCE_FIELD = 'distance';

    /**
     * Get search radius in KM.
     *
     * @return int
     */
    public function getRadius(): int;

    /**
     * Requested country
     *
     * @return string
     */
    public function getCountry(): string;

    /**
     * Requested region
     *
     * @return string|null
     */
    public function getRegion(): ?string;

    /**
     * Requested city
     *
     * @return string|null
     */
    public function getCity(): ?string;

    /**
     * Requested postcode
     *
     * @return string|null
     */
    public function getPostcode(): ?string;
}
