<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupApi\Api\Data\SearchRequest;

/**
 * Filter for Pickup Location search.
 */
interface FilterInterface
{
    /**
     * Value of the filter
     *
     * @return string
     */
    public function getValue(): string;

    /**
     * Condition type for the filter
     *
     * @return string
     */
    public function getConditionType(): string;
}
