<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupApi\Model\SearchRequest;

use Magento\Framework\Api\SimpleBuilderInterface;
use Magento\InventoryInStorePickupApi\Api\Data\SearchRequest\AddressFilterInterface;

/**
 * Address Filter Builder.
 *
 * @api
 */
interface AddressFilterBuilderInterface extends SimpleBuilderInterface
{
    /**
     * Build Address Filter.
     *
     * @return AddressFilterInterface|null
     */
    public function create(): ?AddressFilterInterface;

    /**
     * Set Street filter.
     *
     * @param string $street
     * @param string|null $condition
     *
     * @return AddressFilterBuilderInterface
     */
    public function setStreetFilter(string $street, ?string $condition = null): self;

    /**
     * Set Postcode filter.
     *
     * @param string $postcode
     * @param string|null $condition
     *
     * @return AddressFilterBuilderInterface
     */
    public function setPostcodeFilter(string $postcode, ?string $condition = null): self;

    /**
     * Set City filter.
     *
     * @param string $city
     * @param string|null $condition
     *
     * @return AddressFilterBuilderInterface
     */
    public function setCityFilter(string $city, ?string $condition = null): self;

    /**
     * Set Region Id filter.
     *
     * @param string $regionId
     * @param string|null $condition
     *
     * @return AddressFilterBuilderInterface
     */
    public function setRegionIdFilter(string $regionId, ?string $condition = null): self;

    /**
     * Set Region filter.
     *
     * @param string $region
     * @param string|null $condition
     *
     * @return AddressFilterBuilderInterface
     */
    public function setRegionFilter(string $region, ?string $condition = null): self;

    /**
     * Set Country filter.
     *
     * @param string $country
     * @param string|null $condition
     *
     * @return AddressFilterBuilderInterface
     */
    public function setCountryFilter(string $country, ?string $condition): self;
}
