<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Model;

use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\CatalogInventory\Api\StockStatusCriteriaInterface;
use Magento\CatalogInventory\Api\StockStatusCriteriaInterfaceFactory;
use Magento\CatalogInventory\Api\StockStatusRepositoryInterface;
use Magento\InventoryCatalog\Model\Cache\LegacyStockStatusStorage;

/**
 * Cache storage for legacy stock status
 */
class LegacyStockStatusCache
{
    /**
     * @var StockStatusRepositoryInterface
     */
    private $stockStatusRepository;

    /**
     * @var StockStatusCriteriaInterfaceFactory
     */
    private $stockStatusCriteriaFactory;

    /**
     * @var StockConfigurationInterface
     */
    private $stockConfiguration;

    /**
     * @var LegacyStockStatusStorage
     */
    private $legacyStockStatusStorage;

    /**
     * @param StockStatusRepositoryInterface $stockStatusRepository
     * @param StockStatusCriteriaInterfaceFactory $stockStatusCriteriaFactory
     * @param StockConfigurationInterface $stockConfiguration
     * @param LegacyStockStatusStorage $legacyStockStatusStorage
     */
    public function __construct(
        StockStatusRepositoryInterface $stockStatusRepository,
        StockStatusCriteriaInterfaceFactory $stockStatusCriteriaFactory,
        StockConfigurationInterface $stockConfiguration,
        LegacyStockStatusStorage $legacyStockStatusStorage
    ) {
        $this->stockStatusRepository = $stockStatusRepository;
        $this->stockStatusCriteriaFactory = $stockStatusCriteriaFactory;
        $this->stockConfiguration = $stockConfiguration;
        $this->legacyStockStatusStorage = $legacyStockStatusStorage;
    }

    /**
     * Preload stock status into cache for given product IDs
     *
     * @param array $productIds
     * @return void
     */
    public function execute(array $productIds): void
    {
        $scopeId = $this->stockConfiguration->getDefaultScopeId();
        /** @var StockStatusCriteriaInterface $criteria */
        $criteria = $this->stockStatusCriteriaFactory->create();
        $criteria->setProductsFilter($productIds);
        $criteria->setScopeFilter($scopeId);
        $collection = $this->stockStatusRepository->getList($criteria);
        foreach ($collection->getItems() as $item) {
            $this->legacyStockStatusStorage->set((int)$item->getProductId(), $item, $scopeId);
        }
    }
}
