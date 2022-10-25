<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalogApi\Model;

interface SortableBySaleabilityInterface
{
    /**
     * Check, if sortable by saleability is true/false
     *
     * @return bool
     */
    public function isSortableBySaleability():bool;
}
