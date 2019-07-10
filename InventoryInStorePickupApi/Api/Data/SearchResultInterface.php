<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\InventoryInStorePickupApi\Api\Data;

/**
 * Search results for providing nearby pickup locations
 * @api
 */
interface SearchResultInterface
{
    /**
     * Get items list.
     *
     * @return \Magento\InventoryInStorePickupApi\Api\Data\PickupLocationInterface[]
     */
    public function getItems(): array;

    /**
     * Set items list.
     *
     * @param \Magento\InventoryInStorePickupApi\Api\Data\PickupLocationInterface[] $items
     *
     * @return $this
     */
    public function setItems(array $items): SearchResultInterface;

    /**
     * Get search criteria.
     *
     * @return SearchCriteriaInterface
     */
    public function getSearchCriteria(): SearchCriteriaInterface;

    /**
     * Set search criteria.
     *
     * @param \Magento\InventoryInStorePickupApi\Api\Data\SearchCriteriaInterface $searchCriteria
     *
     * @return $this
     */
    public function setSearchCriteria(
        SearchCriteriaInterface $searchCriteria
    ): SearchResultInterface;

    /**
     * Get total count.
     *
     * @return int
     */
    public function getTotalCount(): int;

    /**
     * Set total count.
     *
     * @param int $totalCount
     *
     * @return $this
     */
    public function setTotalCount(int $totalCount): SearchResultInterface;
}
