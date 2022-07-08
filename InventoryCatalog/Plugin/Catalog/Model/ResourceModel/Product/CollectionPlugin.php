<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Plugin\Catalog\Model\ResourceModel\Product;

use Magento\Catalog\Helper\Data;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\Framework\DB\Select;

/**
 * Class Collection plugin applying sort order
 */
class CollectionPlugin
{
    private const OUT_OF_STOCK_TO_BOTTOM = 2;

    /**
     * @var StockConfigurationInterface
     */
    private $stockConfiguration;

    /**
     * @var Data
     */
    private $categoryHelper;

    /**
     * Collection plugin constructor
     *
     * @param StockConfigurationInterface $stockConfiguration
     * @param Data $categoryHelper
     */
    public function __construct(
        StockConfigurationInterface $stockConfiguration,
        Data $categoryHelper
    ) {
        $this->stockConfiguration = $stockConfiguration;
        $this->categoryHelper = $categoryHelper;
    }

    /**
     * Setting order and determine flags
     *
     * @param Collection $subject
     * @param mixed $attribute
     * @param string $dir
     * @return array
     */
    public function beforeSetOrder(
        Collection $subject,
        mixed $attribute,
        string $dir = Select::SQL_DESC
    ): array {
        if ($this->stockConfiguration->isShowOutOfStock()) {
            $subject->setFlag('is_processing', true);
            $this->applyOutOfStockSortOrders($subject);
            $subject->setFlag('is_processing', false);
        }

        return [$attribute, $dir];
    }

    /**
     * Apply sort orders
     *
     * @param Collection $collection
     * @return void
     */
    private function applyOutOfStockSortOrders(Collection $collection): void
    {
        if (!$collection->getFlag('is_sorted_by_oos')) {
            $collection->setFlag('is_sorted_by_oos', true);

            if ($this->isOutOfStockBottom()) {
                $collection->setOrder('is_out_of_stock', Select::SQL_DESC);
            }
        }
    }

    /**
     * Check if automatic sorting value for Category is set to OUT_OF_STOCK_TO_BOTTOM
     *
     * @return bool
     */
    private function isOutOfStockBottom(): bool
    {
        $currentCategory = $this->categoryHelper->getCategory();
        if ($currentCategory) {
            return (int)$currentCategory->getData('automatic_sorting') === self::OUT_OF_STOCK_TO_BOTTOM;
        }

        return false;
    }

    /**
     * Determine and set order if necessary
     *
     * @param Collection $subject
     * @param mixed $attribute
     * @param string $dir
     * @return array
     */
    public function beforeAddOrder(
        Collection $subject,
        mixed $attribute,
        string $dir = Select::SQL_DESC
    ): array {
        if (!$subject->getFlag('is_processing')) {
            $result = $this->beforeSetOrder($subject, $attribute, $dir);
        }

        return $result ?? [$attribute, $dir];
    }
}
