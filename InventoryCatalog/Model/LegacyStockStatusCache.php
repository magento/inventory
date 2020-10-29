<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Model;

use Magento\CatalogInventory\Api\Data\StockStatusInterface;
use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\CatalogInventory\Api\StockStatusCriteriaInterface;
use Magento\CatalogInventory\Api\StockStatusCriteriaInterfaceFactory;
use Magento\CatalogInventory\Api\StockStatusRepositoryInterface;

/**
 * Cache storage for legacy stock status
 */
class LegacyStockStatusCache
{
    /**
     * @var array
     */
    private $storage = [];
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
     * @param StockStatusRepositoryInterface $stockStatusRepository
     * @param StockStatusCriteriaInterfaceFactory $stockStatusCriteriaFactory
     * @param StockConfigurationInterface $stockConfiguration
     */
    public function __construct(
        StockStatusRepositoryInterface $stockStatusRepository,
        StockStatusCriteriaInterfaceFactory $stockStatusCriteriaFactory,
        StockConfigurationInterface $stockConfiguration
    ) {
        $this->stockStatusRepository = $stockStatusRepository;
        $this->stockStatusCriteriaFactory = $stockStatusCriteriaFactory;
        $this->stockConfiguration = $stockConfiguration;
    }

    /**
     * Preload stock status into cache for given product IDs
     *
     * @param array $productIds
     * @param int|null $scopeId
     * @return array
     */
    public function preload(array $productIds, ?int $scopeId = null): array
    {
        $scopeId = $scopeId ?? (int) $this->stockConfiguration->getDefaultScopeId();
        /** @var StockStatusCriteriaInterface $criteria */
        $criteria = $this->stockStatusCriteriaFactory->create();
        $criteria->setProductsFilter($productIds);
        $criteria->setScopeFilter($scopeId);
        $collection = $this->stockStatusRepository->getList($criteria);
        foreach ($collection->getItems() as $item) {
            $this->save((int) $item->getProductId(), $item, $scopeId);
        }
        return $collection->getItems();
    }

    /**
     * Load stock status from cache
     *
     * @param int $productId
     * @param int|null $scopeId
     * @return StockStatusInterface
     */
    public function load(int $productId, ?int $scopeId = null): ?StockStatusInterface
    {
        $scopeId = $scopeId ?? (int) $this->stockConfiguration->getDefaultScopeId();
        return $this->storage[$productId][$scopeId] ?? null;
    }

    /**
     * Save stock status into cache
     *
     * @param int $productId
     * @param StockStatusInterface $value
     * @param int|null $scopeId
     * @return void
     */
    public function save(int $productId, StockStatusInterface $value, ?int $scopeId = null): void
    {
        $scopeId = $scopeId ?? (int) $this->stockConfiguration->getDefaultScopeId();
        $this->storage[$productId][$scopeId] = $value;
    }

    /**
     * Clean cache
     *
     * @return void
     */
    public function clean()
    {
        $this->storage = [];
    }
}
