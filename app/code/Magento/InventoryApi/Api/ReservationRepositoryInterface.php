<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\InventoryApi\Api;

use Magento\Framework\Api\SearchCriteriaInterface;

/**
 * @api
 */
interface ReservationRepositoryInterface
{
    /**
     * Find Reservations by SearchCriteria
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface|null $searchCriteria
     * @return \Magento\InventoryApi\Api\Data\ReservationSearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria = null);
}
