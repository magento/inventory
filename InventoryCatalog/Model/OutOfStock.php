<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Model;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\ResourceModel\Product\Collection;

class OutOfStock implements OutOfStockInterface
{

    /**
     * @param Category $category
     * @param Collection $collection
     * @return bool
     */
    public function isOutOfStockBottom(Category $category, Collection $collection): bool
    {
        return true;
    }
}
