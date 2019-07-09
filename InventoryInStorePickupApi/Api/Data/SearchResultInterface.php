<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\InventoryInStorePickupApi\Api\Data;

use Magento\InventoryInStorePickupApi\Api\Data\SearchCriteria\GetNearbyLocationsCriteriaInterface;

/**
 * Search results for providing nearby pickup locations
 */
interface SearchResultInterface
{
    /**
     * Get items list.
     *
     * @return PickupLocationInterface[]
     */
    public function getItems(): array;

    /**
     * Set items list.
     *
     * @param PickupLocationInterface[] $items
     *
     * @return $this
     */
    public function setItems(array $items);

    /**
     * Get search criteria.
     *
     * @return GetNearbyLocationsCriteriaInterface
     */
    public function getSearchCriteria(): GetNearbyLocationsCriteriaInterface;

    /**
     * Set search criteria.
     *
     * @param GetNearbyLocationsCriteriaInterface $searchCriteria
     *
     * @return $this
     */
    public function setSearchCriteria(
        GetNearbyLocationsCriteriaInterface $searchCriteria
    );

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
    public function setTotalCount(int $totalCount);
}
