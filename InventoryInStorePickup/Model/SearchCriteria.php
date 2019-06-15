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
class SearchCriteria extends \Magento\Framework\Api\SearchCriteria implements SearchCriteriaInterface
{
    private const RADIUS = 'radius';
    private const COUNTRY = 'country';
    private const POSTCODE = 'postcode';
    private const REGION = 'region';
    private const CITY = 'city';

    /**
     * @inheritdoc
     */
    public function setRadius(int $radius): SearchCriteriaInterface
    {
        return $this->setData(self::RADIUS, $radius);
    }

    /**
     * @inheritdoc
     */
    public function getRadius(): int
    {
        return (int)$this->_get(self::RADIUS);
    }

    /**
     * @inheritdoc
     */
    public function setCountry(string $country): SearchCriteriaInterface
    {
        return $this->setData(self::COUNTRY, $country);
    }

    /**
     * @inheritdoc
     */
    public function getCountry(): string
    {
        return (string)$this->_get(self::COUNTRY);
    }

    /**
     * @inheritdoc
     */
    public function setPostcode(?string $postcode): SearchCriteriaInterface
    {
        return $this->setData(self::POSTCODE, $postcode);
    }

    /**
     * @inheritdoc
     */
    public function getPostcode(): ?string
    {
        return $this->_get(self::POSTCODE);
    }

    /**
     * @inheritdoc
     */
    public function setRegion(?string $region): SearchCriteriaInterface
    {
        return $this->setData(self::REGION, $region);
    }

    /**
     * @inheritdoc
     */
    public function getRegion(): ?string
    {
        return $this->_get(self::REGION);
    }

    /**
     * @inheritdoc
     */
    public function setCity(?string $city): SearchCriteriaInterface
    {
        return $this->setData(self::CITY, $city);
    }

    /**
     * @inheritdoc
     */
    public function getCity(): ?string
    {
        return $this->_get(self::CITY);
    }
}
