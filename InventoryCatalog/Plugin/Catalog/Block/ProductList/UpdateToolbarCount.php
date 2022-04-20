<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Plugin\Catalog\Block\ProductList;

use Magento\Catalog\Block\Product\ProductList\Toolbar;
use Magento\Catalog\Helper\Data;
use Magento\Catalog\Model\CategoryFactory;
use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\InventorySalesApi\Api\AreProductsSalableInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Update toolbar count for the category list view
 */
class UpdateToolbarCount
{
    /**
     * @var ScopeConfigInterface
     */
    private $config;

    /**
     * @var Data
     */
    private $categoryHelper;

    /**
     * @var CategoryFactory
     */
    private $categoryFactory;

    /**
     * @var StockRegistryInterface
     */
    private $stockRegistry;

    /**
     * @var StockConfigurationInterface
     */
    private $stockConfiguration;

    /**
     * @var AreProductsSalableInterface
     */
    private $areProductsSalable;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param ScopeConfigInterface $config
     * @param Data $categoryHelper
     * @param CategoryFactory $categoryFactory
     * @param StockRegistryInterface $stockRegistry
     * @param StockConfigurationInterface $stockConfiguration
     * @param AreProductsSalableInterface $areProductsSalable
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ScopeConfigInterface $config,
        Data $categoryHelper,
        CategoryFactory $categoryFactory,
        StockRegistryInterface $stockRegistry,
        StockConfigurationInterface $stockConfiguration,
        AreProductsSalableInterface $areProductsSalable,
        StoreManagerInterface $storeManager
    ) {
        $this->config = $config;
        $this->categoryHelper = $categoryHelper;
        $this->categoryFactory = $categoryFactory;
        $this->stockRegistry = $stockRegistry;
        $this->stockConfiguration = $stockConfiguration;
        $this->areProductsSalable = $areProductsSalable;
        $this->storeManager = $storeManager;
    }

    /**
     * Update toolbar count if store is in single source mode
     *
     * @param Toolbar $subject
     * @param int $result
     * @return int
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @throws LocalizedException
     */
    public function afterGetTotalNum(Toolbar $subject, int $result): int
    {
        if ($this->stockConfiguration->isShowOutOfStock()) {
            $currentCategory = $this->categoryHelper->getCategory();
            $category = $this->categoryFactory->create()->load($currentCategory->getEntityId());
            $defaultScopeId = $this->storeManager->getWebsite()->getCode();
            $stock_id = (int) $this->stockRegistry->getStock($defaultScopeId)->getStockId();
            $skus = [];
            $items = $category->getProductCollection()->getItems();
            array_walk(
                $items,
                function ($item) use (&$skus) {
                    array_push($skus, $item->getSku());
                }
            );
            $salableProducts = $this->areProductsSalable->execute($skus, $stock_id);
            if ($salableProducts) {
                $result = count($salableProducts);
            }
        }
        return $result;
    }
}
