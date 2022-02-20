<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Model\SourceItemsSaveSynchronization;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\Relation;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Api\StockItemRepositoryInterface;
use Magento\CatalogInventory\Api\StockItemCriteriaInterfaceFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\CatalogInventory\Model\Indexer\Stock\Processor;
use Magento\CatalogInventory\Model\Spi\StockStateProviderInterface;
use Magento\CatalogInventory\Model\Stock;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Indexer\CacheContext;
use Magento\InventoryCatalogApi\Model\GetProductIdsBySkusInterface;
use Magento\InventoryCatalog\Model\ResourceModel\SetDataToLegacyStockItem;
use Magento\InventoryCatalog\Model\ResourceModel\SetDataToLegacyStockStatus;
use Magento\InventorySalesApi\Api\AreProductsSalableInterface;

/**
 * Set Qty and status for legacy CatalogInventory Stock Information tables.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SetDataToLegacyCatalogInventory
{
    /**
     * @var SetDataToLegacyStockItem
     */
    private $setDataToLegacyStockItem;

    /**
     * @var SetDataToLegacyStockStatus
     */
    private $setDataToLegacyStockStatus;

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
     * @var StockStateProviderInterface
     */
    private $stockStateProvider;

    /**
     * @var Processor
     */
    private $indexerProcessor;

    /**
     * @var AreProductsSalableInterface
     */
    private $areProductsSalable;

    /**
     * @var EventManager
     */
    private $eventManager;

    /**
     * @var CacheContext
     */
    private $cacheContext;

    /**
     * @var Relation
     */
    private $productRelationResource;

    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @param SetDataToLegacyStockItem $setDataToLegacyStockItem
     * @param StockItemCriteriaInterfaceFactory $legacyStockItemCriteriaFactory
     * @param StockItemRepositoryInterface $legacyStockItemRepository
     * @param GetProductIdsBySkusInterface $getProductIdsBySkus
     * @param StockStateProviderInterface $stockStateProvider
     * @param Processor $indexerProcessor
     * @param SetDataToLegacyStockStatus $setDataToLegacyStockStatus
     * @param AreProductsSalableInterface $areProductsSalable
     * @param EventManager|null $eventManager
     * @param CacheContext|null $cacheContext
     * @param Relation|null $productRelationResource
     * @param ResourceConnection|null $resource
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        SetDataToLegacyStockItem $setDataToLegacyStockItem,
        StockItemCriteriaInterfaceFactory $legacyStockItemCriteriaFactory,
        StockItemRepositoryInterface $legacyStockItemRepository,
        GetProductIdsBySkusInterface $getProductIdsBySkus,
        StockStateProviderInterface $stockStateProvider,
        Processor $indexerProcessor,
        SetDataToLegacyStockStatus $setDataToLegacyStockStatus,
        AreProductsSalableInterface $areProductsSalable,
        ?EventManager $eventManager = null,
        ?CacheContext $cacheContext = null,
        ?Relation $productRelationResource = null,
        ?ResourceConnection $resource = null
    ) {
        $this->setDataToLegacyStockItem = $setDataToLegacyStockItem;
        $this->setDataToLegacyStockStatus = $setDataToLegacyStockStatus;
        $this->legacyStockItemCriteriaFactory = $legacyStockItemCriteriaFactory;
        $this->legacyStockItemRepository = $legacyStockItemRepository;
        $this->getProductIdsBySkus = $getProductIdsBySkus;
        $this->stockStateProvider = $stockStateProvider;
        $this->indexerProcessor = $indexerProcessor;
        $this->areProductsSalable = $areProductsSalable;
        $this->eventManager = $eventManager ?? ObjectManager::getInstance()->get(EventManager::class);
        $this->cacheContext = $cacheContext ?? ObjectManager::getInstance()->get(CacheContext::class);
        $this->productRelationResource = $productRelationResource ?? ObjectManager::getInstance()->get(Relation::class);
        $this->resource = $resource ?? ObjectManager::getInstance()->get(ResourceConnection::class);
    }

    /**
     * Updates Stock information in legacy inventory.
     *
     * @param array $sourceItems
     * @return void
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function execute(array $sourceItems): void
    {
        $skus = [];
        foreach ($sourceItems as $sourceItem) {
            $skus[] = $sourceItem->getSku();
        }

        $stockStatuses = $this->getStockStatuses($skus);
        $productIds = [];
        $stockStatusChanged = false;
        foreach ($sourceItems as $sourceItem) {
            $sku = $sourceItem->getSku();

            try {
                $productId = (int)$this->getProductIdsBySkus->execute([$sku])[$sku];
            } catch (NoSuchEntityException $e) {
                // Skip synchronization of for not existed product
                continue;
            }

            $legacyStockItem = $this->getLegacyStockItem($productId);
            if (null === $legacyStockItem) {
                continue;
            }

            $isInStock = (int)$sourceItem->getStatus();

            if ($legacyStockItem->getManageStock()) {
                $legacyStockItem->setIsInStock($isInStock);
                $legacyStockItem->setQty((float)$sourceItem->getQuantity());

                if (false === $this->stockStateProvider->verifyStock($legacyStockItem)) {
                    $isInStock = 0;
                }
            }

            $this->setDataToLegacyStockItem->execute(
                (string)$sourceItem->getSku(),
                (float)$sourceItem->getQuantity(),
                $isInStock
            );

            if (!$stockStatusChanged) {
                $statusBefore = $this->getProductStockStatus($productId);
            }
            $this->setDataToLegacyStockStatus->execute(
                (string)$sourceItem->getSku(),
                (float)$sourceItem->getQuantity(),
                (int)$stockStatuses[(string)$sourceItem->getSku()]
            );

            if (!$stockStatusChanged) {
                $statusAfter = $this->getProductStockStatus($productId);
                if (isset($statusBefore['stock_status']) && isset($statusAfter['stock_status'])){
                    $stockStatusChanged = !($statusBefore['stock_status'] === $statusAfter['stock_status']);
                }
            }
            $productIds[] = $productId;
        }

        if ($productIds) {
            $this->indexerProcessor->reindexList($productIds);

            if ($stockStatusChanged) {
                $this->cacheClean($productIds);
            }
        }
    }

    /**
     * Returns items stock statuses.
     *
     * @param array $skus
     * @return array
     */
    private function getStockStatuses(array $skus): array
    {
        $stockStatuses = [];
        foreach ($this->areProductsSalable->execute($skus, Stock::DEFAULT_STOCK_ID) as $productSalable) {
            $stockStatuses[$productSalable->getSku()] = $productSalable->isSalable();
        }
        return $stockStatuses;
    }

    /**
     * Returns StockItem from legacy inventory.
     *
     * @param int $productId
     * @return null|StockItemInterface
     */
    private function getLegacyStockItem(int $productId): ?StockItemInterface
    {
        $searchCriteria = $this->legacyStockItemCriteriaFactory->create();

        $searchCriteria->addFilter(StockItemInterface::PRODUCT_ID, StockItemInterface::PRODUCT_ID, $productId);
        $searchCriteria->addFilter(StockItemInterface::STOCK_ID, StockItemInterface::STOCK_ID, Stock::DEFAULT_STOCK_ID);

        $stockItemCollection = $this->legacyStockItemRepository->getList($searchCriteria);
        if ($stockItemCollection->getTotalCount() === 0) {
            return null;
        }

        $stockItems = $stockItemCollection->getItems();
        $stockItem = reset($stockItems);
        return $stockItem;
    }

    /**
     * Clean products cache by product cache tag id
     *
     * @param array $productIds
     * @return void
     */
    private function cacheClean($productIds) : void
    {
        $parentIds = array_merge(...$this->productRelationResource->getRelationsByChildren($productIds));
        $productIds = $parentIds ? array_unique(array_merge($parentIds, $productIds)) : $productIds;

        $this->cacheContext->registerEntities(Product::CACHE_TAG, array_unique($productIds));
        $this->eventManager->dispatch('clean_cache_by_tags', ['object' => $this->cacheContext]);
    }

    /**
     * Get product stock details
     *
     * @param int $productId
     * @return mixed
     */
    private function getProductStockStatus($productId)
    {
        $select = $this->resource->getConnection()->select();
        $select->from($this->resource->getTableName('cataloginventory_stock_status'))
            ->where('product_id = ?', $productId, \Zend_Db::INT_TYPE);
        return $this->resource->getConnection()->fetchRow($select);
    }
}
