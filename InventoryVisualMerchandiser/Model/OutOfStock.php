<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryVisualMerchandiser\Model;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Framework\Module\Manager;
use Magento\VisualMerchandiser\Model\Sorting;

class OutOfStock extends Sorting implements OutOfStockInterface
{
    /**
     * @var Manager
     */
    private $moduleManager;

    /**
     * Constructor for Out Of Stock products resolver
     *
     * @param Manager $moduleManager
     */
    public function __construct(
        Manager $moduleManager
    ) {
        $this->moduleManager = $moduleManager;
    }

    /**
     * @inheritDoc
     */
    public function isOutOfStockBottom(Category $category, Collection $collection): bool
    {
        if ($this->moduleManager->isEnabled('Magento_VisualMerchandiser')) {
            return $category->getAutomaticSorting() &&
                $this->sortClasses[$category->getAutomaticSorting()] === 'OutStockBottom';
        }

        return true;
    }
}
