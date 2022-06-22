<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\VisualMerchandiser\Model;

use Magento\Catalog\Model\Category;
use Magento\InventoryCatalog\Model\OutOfStockInterface;
use Magento\Catalog\Model\ResourceModel\Product\Collection;

class OutOfStock extends Sorting implements OutOfStockInterface
{
    /**
     * @inheritDoc
     */
    public function isOutOfStockBottom(Category $category, Collection $collection): bool
    {
        return $category->getAutomaticSorting() &&
            $this->sortClasses[$category->getAutomaticSorting()] === 'OutStockBottom';
    }
}
