<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupApi\Model;

use InvalidArgumentException;
use Magento\InventoryInStorePickupApi\Api\Data\SearchCriteria\GetNearbyLocationsCriteriaInterface;
use Magento\InventoryInStorePickupApi\Api\Data\SearchCriteria\GetNearbyLocationsCriteriaInterfaceFactory;
use TypeError;

/**
 * Builder for Pickup Locations Search Criteria.
 * @deprecated
 *
 * @api
 */
class SearchCriteriaBuilder
{
    private const RADIUS = 'radius';
    private const COUNTRY = 'country';
    private const POSTCODE = 'postcode';
    private const REGION = 'region';
    private const CITY = 'city';
    private const PAGE_SIZE = 'pageSize';
    private const CURRENT_PAGE = 'currentPage';

    /**
     * @var array
     */
    private $data = [];

    /**
     * @var GetNearbyLocationsCriteriaInterfaceFactory
     */
    private $factory;

    /**
     * @param GetNearbyLocationsCriteriaInterfaceFactory $factory
     */
    public function __construct(
        GetNearbyLocationsCriteriaInterfaceFactory $factory
    ) {
        $this->factory = $factory;
    }

    /**
     * Builds the GetNearbyLocationsCriteria Data Object.
     *
     * @return GetNearbyLocationsCriteriaInterface
     * @throws InvalidArgumentException
     */
    public function create(): GetNearbyLocationsCriteriaInterface
    {
        try {
            return $this->factory->create($this->data);
        } catch (TypeError $error) {
            throw new InvalidArgumentException('Invalid GetNearbyLocationsCriteria arguments given', 0, $error);
        }
    }

    /**
     * Set radius. Required.
     *
     * @param int $radius
     *
     * @return SearchCriteriaBuilder
     */
    public function setRadius(int $radius)
    {
        return $this->set(self::RADIUS, $radius);
    }

    /**
     * Set country. Required.
     *
     * @param string $country
     *
     * @return SearchCriteriaBuilder
     */
    public function setCountry(string $country): self
    {
        return $this->set(self::COUNTRY, $country);
    }

    /**
     * Set postcode. Not required.
     *
     * @param string|null $postcode
     *
     * @return SearchCriteriaBuilder
     */
    public function setPostcode(?string $postcode): self
    {
        return $this->set(self::POSTCODE, $postcode);
    }

    /**
     * Set region. Not required.
     *
     * @param string|null $region
     *
     * @return SearchCriteriaBuilder
     */
    public function setRegion(?string $region): self
    {
        return $this->set(self::REGION, $region);
    }

    /**
     * Set city. Not required.
     *
     * @param string|null $city
     *
     * @return SearchCriteriaBuilder
     */
    public function setCity(?string $city): self
    {
        return $this->set(self::CITY, $city);
    }

    /**
     * Set page size. Not required.
     *
     * @param int $pageSize
     *
     * @return SearchCriteriaBuilder
     */
    public function setPageSize(int $pageSize): self
    {
        return $this->set(self::PAGE_SIZE, $pageSize);
    }

    /**
     * Set current page. Not required.
     *
     * @param int $page
     *
     * @return SearchCriteriaBuilder
     */
    public function setCurrentPage(int $page): self
    {
        return $this->set(self::CURRENT_PAGE, $page);
    }

    /**
     * Set data
     *
     * @param string $key
     * @param mixed $value
     * @return SearchCriteriaBuilder
     */
    private function set(string $key, $value): self
    {
        $this->data[$key] = $value;

        return $this;
    }
}
