<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupApi\Api\Data;

use Magento\Framework\Api\ExtensibleDataInterface;

/**
 * Represents sources projection on In-Store Pickup context.
 * Realisation must follow immutable DTO concept.
 * Partial immutability done according to restriction of current Extension Attributes implementation.
 *
 * @api
 */
interface PickupLocationInterface extends ExtensibleDataInterface
{
    const IS_PICKUP_LOCATION_ACTIVE = 'is_pickup_location_active';
    const FRONTEND_NAME = 'frontend_name';
    const FRONTEND_DESCRIPTION = 'frontend_description';

    /**
     * Get source code of Pickup Location
     *
     * @return string
     */
    public function getSourceCode(): string;

    /**
     * Get Pickup Location name
     *
     * @return string|null
     */
    public function getName(): ?string;

    /**
     * Get Pickup Location contact email
     *
     * @return string|null
     */
    public function getEmail(): ?string;

    /**
     * Get Fax contact info.
     *
     * @return string|null
     */
    public function getFax(): ?string;

    /**
     * Get Pickup Location contact name.
     *
     * @return string|null
     */
    public function getContactName(): ?string;

    /**
     * Get Pickup Location description.
     *
     * @return string|null
     */
    public function getDescription(): ?string;

    /**
     * Get Pickup Location latitude.
     *
     * @return float|null
     */
    public function getLatitude(): ?float;

    /**
     * Get Pickup Location longtitude.
     *
     * @return float|null
     */
    public function getLongitude(): ?float;

    /**
     * Get Pickup Location country ID.
     *
     * @return string|null
     */
    public function getCountryId(): ?string;

    /**
     * Get Pickup Location region ID.
     *
     * @return int|null
     */
    public function getRegionId(): ?int;

    /**
     * Get Pickup Location region.
     *
     * @return string|null
     */
    public function getRegion(): ?string;

    /**
     * Get Pickup Location city.
     *
     * @return string|null
     */
    public function getCity(): ?string;

    /**
     * Get Pickup Location street.
     *
     * @return string|null
     */
    public function getStreet(): ?string;

    /**
     * Get Pickup Location postcode.
     *
     * @return string|null
     */
    public function getPostcode(): ?string;

    /**
     * Get Pickup Location phone.
     *
     * @return string|null
     */
    public function getPhone(): ?string;

    /**
     * Set Extension Attributes for Pickup Location.
     *
     * @param \Magento\InventoryInStorePickupApi\Api\Data\PickupLocationExtensionInterface|null $extensionAttributes
     *
     * @return void
     */
    public function setExtensionAttributes(?PickupLocationExtensionInterface $extensionAttributes): void;

    /**
     * Get Extension Attributes of Pickup Location.
     *
     * @return \Magento\InventoryInStorePickupApi\Api\Data\PickupLocationExtensionInterface|null
     */
    public function getExtensionAttributes(): ?PickupLocationExtensionInterface;
}
