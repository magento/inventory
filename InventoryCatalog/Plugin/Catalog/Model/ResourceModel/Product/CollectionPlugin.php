<?php
/**
 * Copyright 2022 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Plugin\Catalog\Model\ResourceModel\Product;

use Magento\Catalog\Helper\Data;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\Framework\DB\Select;
use Magento\InventoryCatalogApi\Model\SortableBySaleabilityInterface;

/**
 * Collection plugin applying sort order
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
     * @var SortableBySaleabilityInterface
     */
    private $sortableBySaleabilityProvider;

    /**
     * @param StockConfigurationInterface $stockConfiguration
     * @param Data $categoryHelper
     * @param SortableBySaleabilityInterface $sortableBySaleabilityProvider
     */
    public function __construct(
        StockConfigurationInterface $stockConfiguration,
        Data $categoryHelper,
        SortableBySaleabilityInterface $sortableBySaleabilityProvider
    ) {
        $this->stockConfiguration = $stockConfiguration;
        $this->categoryHelper = $categoryHelper;
        $this->sortableBySaleabilityProvider = $sortableBySaleabilityProvider;
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
        $this->applyOutOfStockSortOrders($subject);
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
        if ($this->stockConfiguration->isShowOutOfStock()) {
            $collection->setFlag('is_processing', true);

            if (!$collection->getFlag('is_sorted_by_oos')) {
                $collection->setFlag('is_sorted_by_oos', true);

                if ($this->isOutOfStockBottom() && $this->sortableBySaleabilityProvider->isSortableBySaleability()) {
                    $collection->setOrder(SortableBySaleabilityInterface::IS_OUT_OF_STOCK, Select::SQL_ASC);
                }
            }
            $collection->setFlag('is_processing', false);
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
        mixed $attribute,
        string $dir = Select::SQL_DESC
    ): array {
        if (!$subject->getFlag('is_processing')) {
            $this->applyOutOfStockSortOrders($subject);
        }
        return [$attribute, $dir];
    }
}
