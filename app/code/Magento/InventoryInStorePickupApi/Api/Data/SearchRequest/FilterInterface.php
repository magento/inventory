<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
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
