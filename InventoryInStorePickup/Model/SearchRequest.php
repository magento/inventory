<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model;

use Magento\Framework\Api\SortOrder;
use Magento\InventoryInStorePickupApi\Api\Data\SearchRequest\AddressFilterInterface;
use Magento\InventoryInStorePickupApi\Api\Data\SearchRequest\DistanceFilterInterface;
use Magento\InventoryInStorePickupApi\Api\Data\SearchRequest\FilterInterface;
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
     * @var AddressFilterInterface|null
     */
    private $addressFilter;

    /**
     * @var FilterInterface|null
     */
    private $nameFilter;

    /**
     * @var FilterInterface|null
     */
    private $pickupLocationCodeFilter;

    /**
     * @var SortOrder|null
     */
    private $sortOrders;

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
     * @param AddressFilterInterface|null $addressFilter
     * @param FilterInterface|null $nameFilter
     * @param FilterInterface|null $pickupLocationCodeFilter
     * @param SortOrder[]|null $sortOrder
     * @param SearchRequestExtensionInterface|null $searchRequestExtension
     * @param int|null $pageSize
     * @param int $currentPage
     */
    public function __construct(
        string $scopeCode,
        string $scopeType = SalesChannelInterface::TYPE_WEBSITE,
        ?DistanceFilterInterface $distanceFilter = null,
        ?AddressFilterInterface $addressFilter = null,
        ?FilterInterface $nameFilter = null,
        ?FilterInterface $pickupLocationCodeFilter = null,
        ?array $sortOrders = null,
        ?SearchRequestExtensionInterface $searchRequestExtension = null,
        ?int $pageSize = null,
        int $currentPage = 1
    ) {
        $this->scopeCode = $scopeCode;
        $this->scopeType = $scopeType;
        $this->distanceFilter = $distanceFilter;
        $this->addressFilter = $addressFilter;
        $this->nameFilter = $nameFilter;
        $this->pickupLocationCodeFilter = $pickupLocationCodeFilter;
        $this->sortOrders = $sortOrders;
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
    public function getAddressFilter(): ?AddressFilterInterface
    {
        return $this->addressFilter;
    }

    /**
     * @inheritdoc
     */
    public function getNameFilter(): ?FilterInterface
    {
        return $this->nameFilter;
    }

    /**
     * @inheritdoc
     */
    public function getPickupLocationCodeFilter(): ?FilterInterface
    {
        return $this->pickupLocationCodeFilter;
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
        return $this->sortOrders;
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