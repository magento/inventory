<?php
/**
 * Copyright 2022 Adobe
 * All Rights Reserved.
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
class GetLegacyStockItems implements GetLegacyStockItemsInterface
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
     * @param CacheStorage|null $cacheStorage
     */
    public function __construct(
        StockItemInterfaceFactory $stockItemFactory,
        StockItemCriteriaInterfaceFactory $legacyStockItemCriteriaFactory,
        StockItemRepositoryInterface $legacyStockItemRepository,
        GetProductIdsBySkusInterface $getProductIdsBySkus,
        ?CacheStorage $cacheStorage = null
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
        try {
            $productIds = $this->getProductIdsBySkus->execute($skus);
        } catch (NoSuchEntityException $skuNotFoundInCatalog) {
            return [];
        }
        if (empty($productIds)) {
            return [];
        }
        $searchCriteria = $this->legacyStockItemCriteriaFactory->create();
        $searchCriteria->addFilter(
            StockItemInterface::PRODUCT_ID,
            StockItemInterface::PRODUCT_ID,
            ['in' => $productIds],
            'public'
        );
        // Stock::DEFAULT_STOCK_ID is used until we have proper multi-stock item configuration
        $searchCriteria->addFilter(StockItemInterface::STOCK_ID, StockItemInterface::STOCK_ID, Stock::DEFAULT_STOCK_ID);
        $stockItemCollection = $this->legacyStockItemRepository->getList($searchCriteria);

        return $stockItemCollection->getItems();
    }
}
