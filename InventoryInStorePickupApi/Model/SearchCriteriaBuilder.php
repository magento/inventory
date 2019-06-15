<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupApi\Model;

use Magento\Framework\Api\SearchCriteriaBuilder as FrameworkSearchCriteriaBuilder;
use Magento\InventoryInStorePickupApi\Api\Data\SearchCriteriaInterface;

/**
 * Builder for Pickup Locations Search Criteria.
 *
 * @api
 */
class SearchCriteriaBuilder extends FrameworkSearchCriteriaBuilder
{
    private const RADIUS = 'radius';
    private const COUNTRY = 'country';
    private const POSTCODE = 'postcode';
    private const REGION = 'region';
    private const CITY = 'city';

    /**
     * @inheritdoc
     */
    protected function _getDataObjectType()
    {
        return SearchCriteriaInterface::class;
    }

    /**
     * Builds the SearchCriteria Data Object.
     *
     * @return SearchCriteriaInterface
     */
    public function create(): SearchCriteriaInterface
    {
        return parent::create();
    }

    /**
     * @inheritdoc
     */
    public function setRadius(int $radius)
    {
        return $this->_set(self::RADIUS, $radius);
    }

    /**
     * @inheritdoc
     */
    public function setCountry(string $country): self
    {
        return $this->_set(self::COUNTRY, $country);
    }

    /**
     * @inheritdoc
     */
    public function setPostcode(?string $postcode): self
    {
        return $this->_set(self::POSTCODE, $postcode);
    }
    /**
     * @inheritdoc
     */
    public function setRegion(?string $region): self
    {
        return $this->_set(self::REGION, $region);
    }

    /**
     * @inheritdoc
     */
    public function setCity(?string $city): self
    {
        return $this->_set(self::CITY, $city);
    }
}
