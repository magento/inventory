<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Plugin\Catalog\Model\ResourceModel\Product;

use Magento\Catalog\Helper\Data;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Framework\Module\Manager;
use Magento\Framework\DB\Select;

/**
 * Class Collection plugin applying sort order
 */
class CollectionPlugin
{
    public const OUT_OF_STOCK_TO_BOTTOM = 2;

    /**
     * @var array
     */
    private $skipFlags = [];

    /**
     * @var Manager
     */
    private $moduleManager;

    /**
     * @var Data
     */
    private $categoryHelper;

    /**
     * Collection plugin constructor
     *
     * @param Manager $moduleManager
     * @param Data $categoryHelper
     */
    public function __construct(
        Manager $moduleManager,
        Data $categoryHelper
    ) {
        $this->moduleManager = $moduleManager;
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
        $attribute,
        string $dir = Select::SQL_DESC
    ): array {
        $subject->setFlag('is_processing', true);
        $this->applyOutOfStockSortOrders($subject);

        $flagName = $this->_getFlag($attribute);

        if ($subject->getFlag($flagName)) {
            $this->skipFlags[] = $flagName;
        }

        $subject->setFlag('is_processing', false);
        return [$attribute, $dir];
    }

    /**
     * Get flag by attribute
     *
     * @param string $attribute
     * @return string
     */
    private function _getFlag(string $attribute): string
    {
        return 'sorted_by_' . $attribute;
    }

    /**
     * Try to determine applied sorting attribute flags
     *
     * @param Collection $subject
     * @param callable $proceed
     * @param mixed $attribute
     * @param string $dir
     * @return Collection
     */
    public function aroundSetOrder(
        Collection $subject,
        callable $proceed,
        $attribute,
        string $dir = Select::SQL_DESC
    ): Collection {
        $flagName = $this->_getFlag($attribute);
        if (!in_array($flagName, $this->skipFlags, true)) {
            $proceed($attribute, $dir);
        }

        return $subject;
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
     * Check if automatic sorting value is set to OUT_OF_STOCK_TO_BOTTOM
     *
     * @return bool
     */
    public function isOutOfStockBottom(): bool
    {
        if ($this->moduleManager->isEnabled('Magento_VisualMerchandiser')) {
            $currentCategory = $this->categoryHelper->getCategory();

            return (int)$currentCategory->getData('automatic_sorting') === self::OUT_OF_STOCK_TO_BOTTOM;
        }

        return true;
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
        $attribute,
        string $dir = Select::SQL_DESC
    ): array {
        if (!$subject->getFlag('is_processing')) {
            $result = $this->beforeSetOrder($subject, $attribute, $dir);
        }

        return $result ?? [$attribute, $dir];
    }
}
