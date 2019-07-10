<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\InventoryInStorePickupApi\Api\Data;

/**
 * Common interface for working with Pickup Locations API
 * @api
 */
interface SearchCriteriaInterface
{
    /**
     * Get page size.
     *
     * @return int|null
     */
    public function getPageSize(): ?int;

    /**
     * Get current page.
     * If not specified, 1 is returned by default
     *
     * @return int
     */
    public function getCurrentPage(): int;
}
