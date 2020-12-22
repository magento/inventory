<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
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
     * @param string $stockTable
     * @return void
     */
    public function modify(Select $select, string $stockTable): void;
}
