<?php
/**
 * Copyright 2019 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupApi\Api\Data\SearchRequest;

/**
 * Filter for Pickup Location search.
 *
 * @api
 */
interface FilterInterface
{
    /**
     * Get value.
     *
     * @return string
     */
    public function getValue(): string;

    /**
     * Get Condition Type.
     *
     * @return string
     */
    public function getConditionType(): string;
}
