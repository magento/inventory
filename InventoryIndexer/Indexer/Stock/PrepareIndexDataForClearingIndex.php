<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Indexer\Stock;

use ArrayIterator;

/**
 * Class PrepareIndexDataForClearingIndex
 */
class PrepareIndexDataForClearingIndex
{
    /**
     * Deletes all unnecessary data from indexData for clearing
     *
     * @param ArrayIterator $indexData
     *
     * @return ArrayIterator
     */
    public function execute(ArrayIterator $indexData): ArrayIterator
    {
        $clearIndex = [];
        foreach ($indexData as $indexItem) {
            $clearIndex[]['sku'] = $indexItem['sku'];
        }

        return new ArrayIterator($clearIndex);
    }
}
