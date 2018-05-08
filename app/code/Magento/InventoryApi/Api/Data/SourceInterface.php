<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryApi\Api\Data;

use Magento\Framework\Api\ExtensibleDataInterface;
use Magento\Framework\Api\ImmutableDtoInterface;

/**
 * Represents physical storage, i.e. brick and mortar store or warehouse
 *
 * Used fully qualified namespaces in annotations for proper work of WebApi request parser
 *
 * @api
 */
interface SourceInterface extends ExtensibleDataInterface, ImmutableDtoInterface
{
    /**
     * Constants for keys of data array. Identical to the name of the getter in snake case
     */
    const SOURCE_CODE = 'source_code';
    const NAME = 'name';
    const CONTACT_NAME = 'contact_name';
    const EMAIL = 'email';
    const ENABLED = 'enabled';
    const DESCRIPTION = 'description';
    const LATITUDE = 'latitude';
    const LONGITUDE = 'longitude';
    const COUNTRY_ID = 'country_id';
    const REGION_ID = 'region_id';
    const REGION = 'region';
    const CITY = 'city';
    const STREET = 'street';
    const POSTCODE = 'postcode';
    const PHONE = 'phone';
    const FAX = 'fax';
    const USE_DEFAULT_CARRIER_CONFIG = 'use_default_carrier_config';
    const CARRIER_LINKS = 'carrier_links';

    /**
     * Get source code
     *
     * @return string|null
     */
    public function getSourceCode();

    /**
     * Get source name
     *
     * @return string|null
     */
    public function getName();

    /**
     * Get source email
     *
     * @return string|null
     */
    public function getEmail();

    /**
     * Get source contact name
     *
     * @return string|null
     */
    public function getContactName();

    /**
     * Check if source is enabled. For new entity can be null
     *
     * @return bool|null
     */
    public function isEnabled();

    /**
     * Get source description
     *
     * @return string|null
     */
    public function getDescription();

    /**
     * Get source latitude
     *
     * @return float|null
     */
    public function getLatitude();

    /**
     * Get source longitude
     *
     * @return float|null
     */
    public function getLongitude();

    /**
     * Get source country id
     *
     * @return string|null
     */
    public function getCountryId();

    /**
     * Get region id if source has registered region.
     *
     * @return int|null
     */
    public function getRegionId();

    /**
     * Get region title if source has custom region
     *
     * @return string|null
     */
    public function getRegion();

    /**
     * Get source city
     *
     * @return string|null
     */
    public function getCity();

    /**
     * Get source street name
     *
     * @return string|null
     */
    public function getStreet();

    /**
     * Get source post code
     *
     * @return string|null
     */
    public function getPostcode();

    /**
     * Get source phone number
     *
     * @return string|null
     */
    public function getPhone();

    /**
     * Get source fax
     *
     * @return string|null
     */
    public function getFax();

    /**
     * Check is need to use default config
     *
     * @return bool|null
     */
    public function isUseDefaultCarrierConfig();

    /**
     * @return \Magento\InventoryApi\Api\Data\SourceCarrierLinkInterface[]|null
     */
    public function getCarrierLinks();

    /**
     * Retrieve existing extension attributes object
     *
     * Null for return is specified for proper work SOAP requests parser
     *
     * @return \Magento\InventoryApi\Api\Data\SourceExtensionInterface|null
     */
    public function getExtensionAttributes();

    /**
     * Set an extension attributes object
     *
     * @param \Magento\InventoryApi\Api\Data\SourceExtensionInterface $extensionAttributes
     * @return void
     */
    public function setExtensionAttributes(SourceExtensionInterface $extensionAttributes);
}
