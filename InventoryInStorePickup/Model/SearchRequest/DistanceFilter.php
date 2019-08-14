<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model\SearchRequest;

use Magento\InventoryInStorePickupApi\Api\Data\SearchRequest\DistanceFilterInterface;

/**
 * @inheritdoc
 */
class DistanceFilter implements DistanceFilterInterface
{
    /**
     * @var int
     */
    private $radius;

    /**
     * @var string
     */
    private $country;

    /**
     * @var string|null
     */
    private $region;

    /**
     * @var string|null
     */
    private $city;

    /**
     * @var string|null
     */
    private $postcode;

    /**
     * @param int $radius
     * @param string $country
     * @param string|null $region
     * @param string|null $city
     * @param string|null $postcode
     */
    public function __construct(
        int $radius,
        string $country,
        ?string $region = null,
        ?string $city = null,
        ?string $postcode = null
    ) {
        $this->radius = $radius;
        $this->country = $country;
        $this->region = $region;
        $this->city = $city;
        $this->postcode = $postcode;
    }

    /**
     * @inheritdoc
     */
    public function getRadius(): int
    {
        return $this->radius;
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
    public function getPostcode(): ?string
    {
        return $this->postcode;
    }
}
