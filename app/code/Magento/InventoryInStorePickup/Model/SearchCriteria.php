<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model;

use Magento\InventoryInStorePickupApi\Api\Data\SearchCriteriaInterface;

/**
 * @inheritdoc
 */
class SearchCriteria implements SearchCriteriaInterface
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
    private $postcode;

    /**
     * @var string|null
     */
    private $city;

    /**
     * @inheritdoc
     */
    public function setRadius(int $radius): void
    {
        $this->radius = $radius;
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
    public function setCountry(string $country): void
    {
        $this->country = $country;
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
    public function setPostcode(?string $postcode): void
    {
        $this->postcode = $postcode;
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
    public function setRegion(?string $region): void
    {
        $this->region = $region;
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
    public function setCity(?string $city): void
    {
        $this->city = $city;
    }

    /**
     * @inheritdoc
     */
    public function getCity(): ?string
    {
        return $this->city;
    }
}
