<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfiguration\Model;

use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Api\Data\StockItemInterfaceFactory;
use Magento\CatalogInventory\Api\StockItemCriteriaInterfaceFactory;
use Magento\CatalogInventory\Api\StockItemRepositoryInterface;
use Magento\CatalogInventory\Model\Stock;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryCatalogApi\Model\GetProductIdsBySkusInterface;
use Magento\InventoryConfiguration\Model\LegacyStockItem\CacheStorage;

/**
 * Get legacy stock items entity by skus.
 */
class GetLegacyStockItems
{
    /**
     * @var StockItemInterfaceFactory
     */
    private $stockItemFactory;

    /**
     * @var StockItemCriteriaInterfaceFactory
     */
    private $legacyStockItemCriteriaFactory;

    /**
     * @var StockItemRepositoryInterface
     */
    private $legacyStockItemRepository;

    /**
     * @var GetProductIdsBySkusInterface
     */
    private $getProductIdsBySkus;

    /**
     * @var CacheStorage
     */
    private $cacheStorage;

    /**
     * @param StockItemInterfaceFactory $stockItemFactory
     * @param StockItemCriteriaInterfaceFactory $legacyStockItemCriteriaFactory
     * @param StockItemRepositoryInterface $legacyStockItemRepository
     * @param GetProductIdsBySkusInterface $getProductIdsBySkus
     * @param CacheStorage $cacheStorage
     */
    public function __construct(
        StockItemInterfaceFactory $stockItemFactory,
        StockItemCriteriaInterfaceFactory $legacyStockItemCriteriaFactory,
        StockItemRepositoryInterface $legacyStockItemRepository,
        GetProductIdsBySkusInterface $getProductIdsBySkus,
        CacheStorage $cacheStorage = null
    ) {
        $this->stockItemFactory = $stockItemFactory;
        $this->legacyStockItemCriteriaFactory = $legacyStockItemCriteriaFactory;
        $this->legacyStockItemRepository = $legacyStockItemRepository;
        $this->getProductIdsBySkus = $getProductIdsBySkus;
        $this->cacheStorage = $cacheStorage
            ?: ObjectManager::getInstance()->get(CacheStorage::class);
    }

    /**
     * Get legacy stock items entity by skus.
     *
     * @param string[] $skus
     * @return StockItemInterface[]
     * @throws LocalizedException
     */
    public function execute(array $skus): array
    {
        $cachedLegacyStockItems = [];
        $skus = $this->filterSkusAlreadyInCache($skus, $cachedLegacyStockItems);
        if (empty($skus)) {
            return $cachedLegacyStockItems;
        }
        $searchCriteria = $this->legacyStockItemCriteriaFactory->create();
        try {
            $productIds = $this->getProductIdsBySkus->execute($skus);
        } catch (NoSuchEntityException $skuNotFoundInCatalog) {
            return [];
        }
        $productSkus = array_flip($productIds);
        $searchCriteria->addFilter(
            StockItemInterface::PRODUCT_ID,
            StockItemInterface::PRODUCT_ID,
            ['in' => $productIds],
            'public'
        );
        // Stock::DEFAULT_STOCK_ID is used until we have proper multi-stock item configuration
        $searchCriteria->addFilter(StockItemInterface::STOCK_ID, StockItemInterface::STOCK_ID, Stock::DEFAULT_STOCK_ID);
        $stockItemCollection = $this->legacyStockItemRepository->getList($searchCriteria);
        $stockItems = $stockItemCollection->getItems();
        foreach ($stockItems as $stockItem) {
            $sku = (string)$productSkus[$stockItem->getProductId()];
            if (empty($sku)) {
                continue;
            }
            $this->cacheStorage->set($sku, $stockItem);
        }
        return array_merge($cachedLegacyStockItems, $stockItems);
    }

    /**
     * Only return skus that aren't already in cache.  Adds cached items to $cachedLegacyStockItems.
     *
     * @param string $skus
     * @param array $cachedLegacyStockItems
     * @return StockItemInterface[]
     * @throws LocalizedException
     */
    public function filterSkusAlreadyInCache(array $skus, array &$cachedLegacyStockItems): array
    {
        $filteredSkus = [];
        foreach ($skus as $sku) {
            if ($item = $this->cacheStorage->get($sku)) {
                $cachedLegacyStockItems[] = $item;
                continue;
            }
            $filteredSkus[] = $sku;
        }
        return $filteredSkus;
    }
}
