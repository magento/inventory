<?php
/**
 * Copyright 2020 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalogSearch\Model\Indexer;

use Magento\Framework\DB\Select;

/**
 * Add filter to composite products by child products stock status.
 */
interface SelectModifierInterface
{
    /**
     * Add stock item filter to select
     *
     * @param Select $select
     * @param int $storeId
     * @return void
     */
    public function modify(Select $select, int $storeId): void;
}
