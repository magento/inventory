<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model;

use Magento\InventoryInStorePickupApi\Api\Data\PickupLocationExtensionInterface;
use Magento\InventoryInStorePickupApi\Api\Data\PickupLocationInterface;

/**
 * @inheritdoc
 * @codeCoverageIgnore
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class PickupLocation implements PickupLocationInterface
{
    /**
     * @var PickupLocationExtensionInterface
     */
    private $extensionAttributes;

    /**
     * @var string
     */
    private $sourceCode;

    /**
     * @var string|null
     */
    private $name;

    /**
     * @var string|null
     */
    private $fax;

    /**
     * @var string|null
     */
    private $contactName;

    /**
     * @var string|null
     */
    private $description;

    /**
     * @var float|null
     */
    private $latitude;

    /**
     * @var float|null
     */
    private $longitude;

    /**
     * @var string|null
     */
    private $countryId;

    /**
     * @var int|null
     */
    private $regionId;

    /**
     * @var int|null
     */
    private $region;

    /**
     * @var string|null
     */
    private $city;

    /**
     * @var string|null
     */
    private $street;

    /**
     * @var string|null
     */
    private $postcode;

    /**
     * @var string|null
     */
    private $phone;

    /**
     * @var string|null
     */
    private $email;

    /**
     * @param string $sourceCode
     * @param string|null $name
     * @param string|null $email
     * @param string|null $fax
     * @param string|null $contactName
     * @param string|null $description
     * @param float|null $latitude
     * @param float|null $longitude
     * @param string|null $countryId
     * @param int|null $regionId
     * @param string|null $region
     * @param string|null $city
     * @param string|null $street
     * @param string|null $postcode
     * @param string|null $phone
     * @param PickupLocationExtensionInterface|null $extensionAttributes
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        string $sourceCode,
        ?string $name = null,
        ?string $email = null,
        ?string $fax = null,
        ?string $contactName = null,
        ?string $description = null,
        ?float $latitude = null,
        ?float $longitude = null,
        ?string $countryId = null,
        ?int $regionId = null,
        ?string $region = null,
        ?string $city = null,
        ?string $street = null,
        ?string $postcode = null,
        ?string $phone = null,
        ?PickupLocationExtensionInterface $extensionAttributes = null
    ) {
        $this->sourceCode = $sourceCode;
        $this->name = $name;
        $this->email = $email;
        $this->fax = $fax;
        $this->contactName = $contactName;
        $this->description = $description;
        $this->latitude = $latitude;
        $this->longitude = $longitude;
        $this->countryId = $countryId;
        $this->regionId = $regionId;
        $this->region = $region;
        $this->city = $city;
        $this->street = $street;
        $this->postcode = $postcode;
        $this->phone = $phone;
        $this->extensionAttributes = $extensionAttributes;
    }

    /**
     * @inheritdoc
     */
    public function getSourceCode(): string
    {
        return $this->sourceCode;
    }

    /**
     * @inheritdoc
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @inheritdoc
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * @inheritdoc
     */
    public function getFax(): ?string
    {
        return $this->fax;
    }

    /**
     * @inheritdoc
     */
    public function getContactName(): ?string
    {
        return $this->contactName;
    }

    /**
     * @inheritdoc
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @inheritdoc
     */
    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    /**
     * @inheritdoc
     */
    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    /**
     * @inheritdoc
     */
    public function getCountryId(): ?string
    {
        return $this->countryId;
    }

    /**
     * @inheritdoc
     */
    public function getRegionId(): ?int
    {
        return $this->regionId;
    }

    /**
     * @inheritdoc
     */
    public function getRegion(): ?string
    {
        return $this->region;
    }

    /**
     * @inheritdoc
     */
    public function getCity(): ?string
    {
        return $this->city;
    }

    /**
     * @inheritdoc
     */
    public function getStreet(): ?string
    {
        return $this->street;
    }

    /**
     * @inheritdoc
     */
    public function getPostcode(): ?string
    {
        return $this->postcode;
    }

    /**
     * @inheritdoc
     */
    public function getPhone(): ?string
    {
        return $this->phone;
    }

    /**
     * @inheritdoc
     */
    public function setExtensionAttributes(?PickupLocationExtensionInterface $extensionAttributes): void
    {
        $this->extensionAttributes = $extensionAttributes;
    }

    /**
     * @inheritdoc
     */
    public function getExtensionAttributes(): ?PickupLocationExtensionInterface
    {
        return $this->extensionAttributes;
    }
}
