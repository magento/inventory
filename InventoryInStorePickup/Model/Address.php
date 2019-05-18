<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model;

use Magento\InventoryInStorePickupApi\Api\Data\AddressInterface;

/**
 * {@inheritdoc}
 * @codeCoverageIgnore
 */
class Address implements AddressInterface
{
    /**
     * @var string
     */
    private $country;

    /**
     * @var string|null
     */
    private $postcode;

    /**
     * @var string|null
     */
    private $region;

    /**
     * @var string|null
     */
    private $city;

    /**
     * @param string $country
     * @param string|null $postcode
     * @param string|null $region
     * @param string|null $city
     */
    public function __construct(
        string $country,
        ?string $postcode = null,
        ?string $region = null,
        ?string $city = null
    ) {
        $this->country = $country;
        $this->postcode = $postcode;
        $this->region = $region;
        $this->city = $city;
    }

    /**
     * @inheritdoc
     */
    public function getCountry(): string
    {
        return $this->country;
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
}
