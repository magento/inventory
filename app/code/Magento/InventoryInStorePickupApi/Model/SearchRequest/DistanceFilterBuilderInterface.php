<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupApi\Model\SearchRequest;

use Magento\Framework\Api\SimpleBuilderInterface;
use Magento\InventoryInStorePickupApi\Api\Data\SearchRequest\DistanceFilterInterface;

/**
 * Distance Filter Builder.
 *
 * @api
 */
interface DistanceFilterBuilderInterface extends SimpleBuilderInterface
{
    /**
     * Build Distance Filter.
     *
     * @return DistanceFilterInterface|null
     */
    public function create(): ?DistanceFilterInterface;

    /**
     * Set Radius for Distance Filter.
     *
     * @param int $radius
     *
     * @return DistanceFilterBuilderInterface
     */
    public function setRadius(int $radius): self;

    /**
     * Set Postcode for Distance Filter.
     *
     * @param string $postcode
     *
     * @return DistanceFilterBuilderInterface
     */
    public function setPostcode(string $postcode): self;

    /**
     * Set City for Distance filter.
     *
     * @param string $city
     *
     * @return DistanceFilterBuilderInterface
     */
    public function setCity(string $city): self;

    /**
     * Set Region for Distance filter.
     *
     * @param string $region
     *
     * @return DistanceFilterBuilderInterface
     */
    public function setRegion(string $region): self;

    /**
     * Set Country for Distance filter.
     *
     * @param string $country
     *
     * @return DistanceFilterBuilderInterface
     */
    public function setCountry(string $country): self;
}
