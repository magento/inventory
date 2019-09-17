<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupApi\Model\SearchCriteria;

use Magento\Framework\Api\Filter;
use Magento\Framework\Api\Search\FilterGroup;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SortOrder;

/**
 * Decorator for @see \Magento\Framework\Api\SearchCriteriaBuilder
 *
 * Provides Service Contracts for Search Criteria Builder usage.
 *
 * @api
 */
class SearchCriteriaBuilderDecorator
{
    /**
     * @var SearchCriteriaBuilder
     */
    private $criteriaBuilder;

    /**
     * @param SearchCriteriaBuilder $criteriaBuilder
     */
    public function __construct(SearchCriteriaBuilder $criteriaBuilder)
    {
        $this->criteriaBuilder = $criteriaBuilder;
    }

    /**
     * Builds the SearchCriteria Data Object
     *
     * @return SearchCriteriaInterface
     */
    public function create(): SearchCriteriaInterface
    {
        return $this->criteriaBuilder->create();
    }

    /**
     * Create a filter group based on the filter array provided and add to the filter groups
     *
     * @param Filter[] $filters
     * @return $this
     */
    public function addFilters(array $filters): self
    {
        $this->criteriaBuilder->addFilters($filters);

        return $this;
    }

    /**
     * Add filter to the Search Criteria.
     *
     * @param string $field
     * @param string $value
     * @param string $conditionType
     * @return $this
     */
    public function addFilter(string $field, string $value, string $conditionType = 'eq'): self
    {
        $this->criteriaBuilder->addFilter($field, $value, $conditionType);

        return $this;
    }

    /**
     * Set filter groups.
     *
     * @param FilterGroup[] $filterGroups
     * @return $this
     */
    public function setFilterGroups(array $filterGroups): self
    {
        $this->criteriaBuilder->setFilterGroups($filterGroups);

        return $this;
    }

    /**
     * Add sort order.
     *
     * @param SortOrder $sortOrder
     * @return $this
     */
    public function addSortOrder(SortOrder $sortOrder): self
    {
        $this->criteriaBuilder->addSortOrder($sortOrder);

        return $this;
    }

    /**
     * Set sort orders.
     *
     * @param SortOrder[] $sortOrders
     * @return $this
     */
    public function setSortOrders(array $sortOrders): self
    {
        $this->criteriaBuilder->setSortOrders($sortOrders);

        return $this;
    }

    /**
     * Set page size.
     *
     * @param int $pageSize
     * @return $this
     */
    public function setPageSize(int $pageSize): self
    {
        $this->criteriaBuilder->setPageSize($pageSize);

        return $this;
    }

    /**
     * Set current page.
     *
     * @param int $currentPage
     * @return $this
     */
    public function setCurrentPage(int $currentPage): self
    {
        $this->criteriaBuilder->setCurrentPage($currentPage);

        return $this;
    }

    /**
     * Return data Object data.
     *
     * @return array
     */
    public function getData(): array
    {
        return $this->criteriaBuilder->getData();
    }
}
