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
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryCatalogApi\Model\GetProductIdsBySkusInterface;
use Magento\InventoryConfiguration\Model\LegacyStockItem\CacheStorage;

/**
 * Get legacy stock item entity by sku.
 */
class GetLegacyStockItem
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
     * @var GetLegacyStockItemsInterface
     */
    private $getLegacyStockItems;

    /**
     * @param StockItemInterfaceFactory $stockItemFactory
     * @param StockItemCriteriaInterfaceFactory $legacyStockItemCriteriaFactory
     * @param StockItemRepositoryInterface $legacyStockItemRepository
     * @param GetProductIdsBySkusInterface $getProductIdsBySkus
     * @param CacheStorage|null $cacheStorage
     * @param GetLegacyStockItemsInterface|null $getLegacyStockItems
     */
    public function __construct(
        StockItemInterfaceFactory $stockItemFactory,
        StockItemCriteriaInterfaceFactory $legacyStockItemCriteriaFactory,
        StockItemRepositoryInterface $legacyStockItemRepository,
        GetProductIdsBySkusInterface $getProductIdsBySkus,
        ?CacheStorage $cacheStorage = null,
        ?GetLegacyStockItemsInterface $getLegacyStockItems = null
    ) {
        $this->stockItemFactory = $stockItemFactory;
        $this->legacyStockItemCriteriaFactory = $legacyStockItemCriteriaFactory;
        $this->legacyStockItemRepository = $legacyStockItemRepository;
        $this->getProductIdsBySkus = $getProductIdsBySkus;
        $this->cacheStorage = $cacheStorage
            ?: ObjectManager::getInstance()->get(CacheStorage::class);
        $this->getLegacyStockItems = $getLegacyStockItems
            ?? ObjectManager::getInstance()->get(GetLegacyStockItemsInterface::class);
    }

    /**
     * Get legacy stock item entity by sku.  Uses cache.
     *
     * @param string $sku
     * @return StockItemInterface
     * @throws LocalizedException
     */
    public function execute(string $sku): StockItemInterface
    {
        $item = $this->getLegacyStockItemBySku($sku);
        /* Avoid add to cache a new item */
        if ($item->getItemId()) {
            $this->cacheStorage->set($sku, $item);
        }
        return $item;
    }

    /**
     * Get legacy stock item entity by sku.
     *
     * @param string $sku
     * @return StockItemInterface
     * @throws LocalizedException
     */
    public function getLegacyStockItemBySku(string $sku): StockItemInterface
    {
        try {
            $this->getProductIdsBySkus->execute([$sku])[$sku];
        } catch (NoSuchEntityException $skuNotFoundInCatalog) {
            $stockItem = $this->stockItemFactory->create();
            // Make possible to Manage Stock for Products removed from Catalog
            $stockItem->setManageStock(true);
            return $stockItem;
        }
        $stockItems = $this->getLegacyStockItems->execute([$sku]);
        $stockItem = reset($stockItems);
        if (!$stockItem) {
            return $this->stockItemFactory->create();
        }
        return $stockItem;
    }
}
