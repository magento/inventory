<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model;

use Magento\Framework\Api\SortOrder;
use Magento\InventoryInStorePickupApi\Api\Data\SearchRequest\FiltersInterface;
use Magento\InventoryInStorePickupApi\Api\Data\SearchRequest\DistanceFilterInterface;
use Magento\InventoryInStorePickupApi\Api\Data\SearchRequestExtensionInterface;
use Magento\InventoryInStorePickupApi\Api\Data\SearchRequestInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;

/**
 * @inheritdoc
 */
class SearchRequest implements SearchRequestInterface
{
    /**
     * @var string
     */
    private $scopeCode;

    /**
     * @var string
     */
    private $scopeType;

    /**
     * @var DistanceFilterInterface|null
     */
    private $distanceFilter;

    /**
     * @var FiltersInterface|null
     */
    private $filterSet;

    /**
     * @var SortOrder[]|null
     */
    private $sort;

    /**
     * @var SearchRequestExtensionInterface|null
     */
    private $searchRequestExtension;

    /**
     * @var int|null
     */
    private $pageSize;

    /**
     * @var int
     */
    private $currentPage;

    /**
     * @param string $scopeCode
     * @param string $scopeType
     * @param DistanceFilterInterface|null $distanceFilter
     * @param FiltersInterface|null $filterSet
     * @param SortOrder[]|null $sort
     * @param SearchRequestExtensionInterface|null $searchRequestExtension
     * @param int|null $pageSize
     * @param int $currentPage
     */
    public function __construct(
        string $scopeCode,
        string $scopeType = SalesChannelInterface::TYPE_WEBSITE,
        ?DistanceFilterInterface $distanceFilter = null,
        ?FiltersInterface $filterSet = null,
        ?array $sort = null,
        ?SearchRequestExtensionInterface $searchRequestExtension = null,
        ?int $pageSize = null,
        int $currentPage = 1
    ) {
        $this->scopeCode = $scopeCode;
        $this->scopeType = $scopeType;
        $this->distanceFilter = $distanceFilter;
        $this->filterSet = $filterSet;
        $this->sort = $sort;
        $this->searchRequestExtension = $searchRequestExtension;
        $this->pageSize = $pageSize;
        $this->currentPage = $currentPage;
    }

    /**
     * @inheritdoc
     */
    public function getDistanceFilter(): ?DistanceFilterInterface
    {
        return $this->distanceFilter;
    }

    /**
     * @inheritdoc
     */
    public function getFilters(): ?FiltersInterface
    {
        return $this->filterSet;
    }

    /**
     * @inheritdoc
     */
    public function getPageSize(): ?int
    {
        return $this->pageSize;
    }

    /**
     * @inheritdoc
     */
    public function getCurrentPage(): int
    {
        return $this->currentPage;
    }

    /**
     * @inheritdoc
     */
    public function getScopeType(): string
    {
        return $this->scopeType;
    }

    /**
     * @inheritdoc
     */
    public function getScopeCode(): string
    {
        return $this->scopeCode;
    }

    /**
     * @inheritdoc
     */
    public function getSort(): ?array
    {
        return $this->sort;
    }

    /**
     * @inheritdoc
     */
    public function setExtensionAttributes(?SearchRequestExtensionInterface $extensionAttributes): void
    {
        $this->searchRequestExtension = $extensionAttributes;
    }

    /**
     * @inheritdoc
     */
    public function getExtensionAttributes(): ?SearchRequestExtensionInterface
    {
        return $this->searchRequestExtension;
    }
}
