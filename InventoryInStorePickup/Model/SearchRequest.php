<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model;

use Magento\Framework\Api\SortOrder;
use Magento\InventoryInStorePickupApi\Api\Data\SearchRequest\FiltersInterface;
use Magento\InventoryInStorePickupApi\Api\Data\SearchRequest\AreaInterface;
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
     * @var AreaInterface|null
     */
    private $area;

    /**
     * @var FiltersInterface|null
     */
    private $filters;

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
     * @param AreaInterface|null $area
     * @param FiltersInterface|null $filters
     * @param SortOrder[]|null $sort
     * @param SearchRequestExtensionInterface|null $searchRequestExtension
     * @param int|null $pageSize
     * @param int $currentPage
     */
    public function __construct(
        string $scopeCode,
        string $scopeType = SalesChannelInterface::TYPE_WEBSITE,
        ?AreaInterface $area = null,
        ?FiltersInterface $filters = null,
        ?array $sort = null,
        ?SearchRequestExtensionInterface $searchRequestExtension = null,
        ?int $pageSize = null,
        int $currentPage = 1
    ) {
        $this->scopeCode = $scopeCode;
        $this->scopeType = $scopeType;
        $this->area = $area;
        $this->filters = $filters;
        $this->sort = $sort;
        $this->searchRequestExtension = $searchRequestExtension;
        $this->pageSize = $pageSize;
        $this->currentPage = $currentPage;
    }

    /**
     * @inheritdoc
     */
    public function getArea(): ?AreaInterface
    {
        return $this->area;
    }

    /**
     * @inheritdoc
     */
    public function getFilters(): ?FiltersInterface
    {
        return $this->filters;
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
