<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\InventoryApi\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

interface ReservationSearchResultsInterface extends SearchResultsInterface
{
    /**
     * Get items list.
     *
     * @return \Magento\InventoryApi\Api\Data\ReservationInterface[]
     */
    public function getItems();
}
