<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalogSearch\Plugin\Model\ResourceModel\Fulltext;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Helper\Data;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\OutOfStockInterface;
use Magento\CatalogInventory\Model\Configuration;
use Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection;
use Magento\Framework\DB\Select;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Resolve out of stock status for product sorting attribute
 */
class CollectionPlugin
{
    public const OUT_OF_STOCK_TO_BOTTOM = 2;

    /**
     * @var array
     */
    private $skipFlags = [];

    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * @var Data
     */
    private $categoryHelper;

    /**
     * @var OutOfStockInterface
     */
    private $outOfStock;

    /**
     * @var CategoryRepositoryInterface
     */
    private $categoryRepository;

    /**
     * Collection Plugin Constructor
     *
     * @param Configuration $configuration
     * @param Data $categoryHelper
     * @param OutOfStockInterface $outOfStock
     * @param CategoryRepositoryInterface $categoryRepository
     */
    public function __construct(
        Configuration $configuration,
        Data $categoryHelper,
        OutOfStockInterface $outOfStock,
        CategoryRepositoryInterface $categoryRepository
    ) {
        $this->configuration = $configuration;
        $this->categoryHelper = $categoryHelper;
        $this->outOfStock = $outOfStock;
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * Add sorting attribute for out of stock on first place
     *
     * @param Collection $subject
     * @param string $attribute
     * @param string $dir
     * @return array
     * @throws NoSuchEntityException
     */
    public function beforeSetOrder(
        Collection $subject,
        string $attribute,
        string $dir = Select::SQL_DESC
    ): array {
        if (!$subject->getFlag('is_sorted_by_oos')) {
            $subject->setFlag('is_sorted_by_oos', true);
            $currentCategory = $this->categoryHelper->getCategory();
            /* @var Category $category */
            $category = $this->categoryRepository->get($currentCategory->getId());

            if ($this->outOfStock->isOutOfStockBottom($category, $subject)
                && $this->configuration->isShowOutOfStock($subject->getStoreId())) {
                $subject->addAttributeToSort('is_out_of_stock', Select::SQL_DESC);
            }
        }

        $flagName = $this->_getFlag($attribute);

        if ($subject->getFlag($flagName)) {
            $this->skipFlags[] = $flagName;
        }

        return [$attribute, $dir];
    }

    /**
     * Proceed with other sorting attribute
     *
     * @param Collection $subject
     * @param callable $proceed
     * @param string $attribute
     * @param string $dir
     * @return Collection
     */
    public function aroundSetOrder(
        Collection $subject,
        callable $proceed,
        string $attribute,
        string $dir = Select::SQL_DESC
    ): Collection {
        $flagName = $this->_getFlag($attribute);
        if (!in_array($flagName, $this->skipFlags, true)) {
            $proceed($attribute, $dir);
        }

        return $subject;
    }

    /**
     * Check if automatic sorting for category is set
     *
     * @return bool
     */
    private function isOutOfStockBottom():bool
    {
        //It'll work only when EE repository will be there
        $attributeCode = 'automatic_sorting';
        $currentCategory = $this->categoryHelper->getCategory();

        return (int)$currentCategory->getData($attributeCode) === self::OUT_OF_STOCK_TO_BOTTOM;
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
}
