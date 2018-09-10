<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryApi\Api\Data;

use Magento\Framework\Api\ExtensibleDataInterface;

/**
 * Represents physical storage, i.e. brick and mortar store or warehouse
 *
 * Used fully qualified namespaces in annotations for proper work of WebApi request parser
 *
 * @api
 */
interface SourceInterface extends ExtensibleDataInterface
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
    public function getSourceCode(): ? string;

    /**
     * Set source code
     *
     * @param string|null $sourceCode
     * @return void
     */
    public function setSourceCode(?string $sourceCode);

    /**
     * Get source name
     *
     * @return string|null
     */
    public function getName(): ? string;

    /**
     * Set source name
     *
     * @param string|null $name
     * @return void
     */
    public function setName(?string $name);

    /**
     * Get source email
     *
     * @return string|null
     */
    public function getEmail(): ? string;

    /**
     * Set source email
     *
     * @param string|null $email
     * @return void
     */
    public function setEmail(?string $email);

    /**
     * Get source contact name
     *
     * @return string|null
     */
    public function getContactName(): ? string;

    /**
     * Set source contact name
     *
     * @param string|null $contactName
     * @return void
     */
    public function setContactName(?string $contactName);

    /**
     * Check if source is enabled. For new entity can be null
     *
     * @return bool|null
     */
    public function isEnabled(): ? bool;

    /**
     * Enable or disable source
     *
     * @param bool|null $enabled
     * @return void
     */
    public function setEnabled(?bool $enabled);

    /**
     * Get source description
     *
     * @return string|null
     */
    public function getDescription(): ? string;

    /**
     * Set source description
     *
     * @param string|null $description
     * @return void
     */
    public function setDescription(?string $description);

    /**
     * Get source latitude
     *
     * @return float|null
     */
    public function getLatitude(): ? float;

    /**
     * Set source latitude
     *
     * @param float|null $latitude
     * @return void
     */
    public function setLatitude(?float $latitude);

    /**
     * Get source longitude
     *
     * @return float|null
     */
    public function getLongitude(): ? float;

    /**
     * Set source longitude
     *
     * @param float|null $longitude
     * @return void
     */
    public function setLongitude(?float $longitude);

    /**
     * Get source country id
     *
     * @return string|null
     */
    public function getCountryId(): ? string;

    /**
     * Set source country id
     *
     * @param string|null $countryId
     * @return void
     */
    public function setCountryId(?string $countryId);

    /**
     * Get region id if source has registered region.
     *
     * @return int|null
     */
    public function getRegionId(): ? int;

    /**
     * Set region id if source has registered region.
     *
     * @param int|null $regionId
     * @return void
     */
    public function setRegionId(?int $regionId);

    /**
     * Get region title if source has custom region
     *
     * @return string|null
     */
    public function getRegion(): ? string;

    /**
     * Set source region title
     *
     * @param string|null $region
     * @return void
     */
    public function setRegion(?string $region);

    /**
     * Get source city
     *
     * @return string|null
     */
    public function getCity(): ? string;

    /**
     * Set source city
     *
     * @param string|null $city
     * @return void
     */
    public function setCity(?string $city);

    /**
     * Get source street name
     *
     * @return string|null
     */
    public function getStreet(): ? string;

    /**
     * Set source street name
     *
     * @param string|null $street
     * @return void
     */
    public function setStreet(?string $street);

    /**
     * Get source post code
     *
     * @return string|null
     */
    public function getPostcode(): ? string;

    /**
     * Set source post code
     *
     * @param string|null $postcode
     * @return void
     */
    public function setPostcode(?string $postcode);

    /**
     * Get source phone number
     *
     * @return string|null
     */
    public function getPhone(): ? string;

    /**
     * Set source phone number
     *
     * @param string|null $phone
     * @return void
     */
    public function setPhone(?string $phone);

    /**
     * Get source fax
     *
     * @return string|null
     */
    public function getFax(): ? string;

    /**
     * Set source fax
     *
     * @param string|null $fax
     * @return void
     */
    public function setFax(?string $fax);

    /**
     * Check is need to use default config
     *
     * @return bool|null
     */
    public function isUseDefaultCarrierConfig(): ? bool;

    /**
     * @param bool|null $useDefaultCarrierConfig
     * @return void
     */
    public function setUseDefaultCarrierConfig(?bool $useDefaultCarrierConfig);

    /**
     * @return \Magento\InventoryApi\Api\Data\SourceCarrierLinkInterface[]|null
     */
    public function getCarrierLinks(): ? array;

    /**
     * @param \Magento\InventoryApi\Api\Data\SourceCarrierLinkInterface[]|null $carrierLinks
     * @return void
     */
    public function setCarrierLinks(?array $carrierLinks);

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
