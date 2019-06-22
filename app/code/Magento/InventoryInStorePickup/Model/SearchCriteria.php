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
     * @var int|null
     */
    private $pageSize;

    /**
     * @var int
     */
    private $currentPage = 1;

    /**
     * @param int $radius
     * @param string $country
     * @param string|null $postcode
     * @param string|null $region
     * @param string|null $city
     * @param int|null $pageSize
     * @param int $currentPage
     */
    public function __construct(
        int $radius,
        string $country,
        ?string $postcode = null,
        ?string $region = null,
        ?string $city = null,
        ?int $pageSize = null,
        int $currentPage = 1
    ) {
        $this->radius = $radius;
        $this->country = $country;
        $this->postcode = $postcode;
        $this->region = $region;
        $this->city = $city;
        $this->pageSize = $pageSize;
        $this->currentPage = $currentPage;
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

    /**
     * @inheritDoc
     */
    public function getPageSize(): ?int
    {
        return $this->pageSize;
    }

    /**
     * @inheritDoc
     */
    public function getCurrentPage(): int
    {
        return $this->currentPage;
    }
}
