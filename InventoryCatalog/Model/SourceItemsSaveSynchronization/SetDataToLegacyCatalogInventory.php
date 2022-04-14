<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Model\SourceItemsSaveSynchronization;

use Magento\Catalog\Model\Indexer\Product\Price\Processor as PriceIndexProcessor;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Api\StockItemRepositoryInterface;
use Magento\CatalogInventory\Api\StockItemCriteriaInterfaceFactory;
use Magento\CatalogInventory\Model\Indexer\Stock\CacheCleaner;
use Magento\CatalogInventory\Model\Indexer\Stock\Processor;
use Magento\CatalogInventory\Model\Spi\StockStateProviderInterface;
use Magento\CatalogInventory\Model\Stock;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryCatalogApi\Model\GetProductIdsBySkusInterface;
use Magento\InventoryCatalog\Model\ResourceModel\SetDataToLegacyStockItem;
use Magento\InventoryCatalog\Model\ResourceModel\SetDataToLegacyStockStatus;
use Magento\InventorySalesApi\Api\AreProductsSalableInterface;
use Magento\InventorySalesApi\Model\GetStockItemDataInterface;

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
     * @var CacheCleaner
     */
    private $cacheCleaner;

    /**
     * @var GetStockItemDataInterface
     */
    private $getStockItemData;

    /**
     * @var PriceIndexProcessor
     */
    private $priceIndexProcessor;

    /**
     * @param SetDataToLegacyStockItem $setDataToLegacyStockItem
     * @param StockItemCriteriaInterfaceFactory $legacyStockItemCriteriaFactory
     * @param StockItemRepositoryInterface $legacyStockItemRepository
     * @param GetProductIdsBySkusInterface $getProductIdsBySkus
     * @param StockStateProviderInterface $stockStateProvider
     * @param Processor $indexerProcessor
     * @param SetDataToLegacyStockStatus $setDataToLegacyStockStatus
     * @param AreProductsSalableInterface $areProductsSalable
     * @param CacheCleaner|null $cacheCleaner
     * @param GetStockItemDataInterface|null $getStockItemData
     * @param PriceIndexProcessor|null $priceIndexProcessor
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
        ?CacheCleaner $cacheCleaner = null,
        ?GetStockItemDataInterface $getStockItemData = null,
        ?PriceIndexProcessor $priceIndexProcessor = null
    ) {
        $this->setDataToLegacyStockItem = $setDataToLegacyStockItem;
        $this->setDataToLegacyStockStatus = $setDataToLegacyStockStatus;
        $this->legacyStockItemCriteriaFactory = $legacyStockItemCriteriaFactory;
        $this->legacyStockItemRepository = $legacyStockItemRepository;
        $this->getProductIdsBySkus = $getProductIdsBySkus;
        $this->stockStateProvider = $stockStateProvider;
        $this->indexerProcessor = $indexerProcessor;
        $this->areProductsSalable = $areProductsSalable;
        $this->cacheCleaner = $cacheCleaner ?? ObjectManager::getInstance()->get(CacheCleaner::class);
        $this->getStockItemData = $getStockItemData
            ?: ObjectManager::getInstance()->get(GetStockItemDataInterface::class);
        $this->priceIndexProcessor = $priceIndexProcessor
            ?: ObjectManager::getInstance()->get(PriceIndexProcessor::class);
    }

    /**
     * Updates Stock information in legacy inventory.
     *
     * @param array $sourceItems
     */
    public function execute(array $sourceItems): void
    {
        $productIds = [];
        foreach ($sourceItems as $sourceItem) {
            $sku = $sourceItem->getSku();

            try {
                $productIds[] = (int)$this->getProductIdsBySkus->execute([$sku])[$sku];
            } catch (NoSuchEntityException $e) {
                // Skip synchronization of for not existed product
                continue;
            }
        }
        if (count($productIds) > 0) {
            $this->cacheCleaner->clean(
                $productIds,
                function () use ($sourceItems) {
                    $this->updateSourceItems($sourceItems);
                }
            );
        }
    }

    /**
     * Updates Stock information in legacy inventory.
     *
     * @param array $sourceItems
     * @return void
     */
    private function updateSourceItems(array $sourceItems): void
    {
        $stockStatuses = $this->getStockStatuses($sourceItems);
        $productIds = [];
        $productIdsForPriceReindex = [];
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

            if ($this->hasStockDataChangedFor($sku, (int) $stockStatuses[(string)$sourceItem->getSku()])) {
                $productIdsForPriceReindex[] = $productId;
            }

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

            $this->setDataToLegacyStockStatus->execute(
                (string)$sourceItem->getSku(),
                (float)$sourceItem->getQuantity(),
                (int)$stockStatuses[(string)$sourceItem->getSku()]
            );
            $productIds[] = $productId;
        }

        if ($productIds) {
            $this->indexerProcessor->reindexList($productIds);
        }

        if ($productIdsForPriceReindex) {
            $this->priceIndexProcessor->reindexList($productIdsForPriceReindex);
        }
    }

    /**
     * Check whether the product stock status has changed
     *
     * @param string $sku
     * @param int $currentStatus
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function hasStockDataChangedFor(string $sku, int $currentStatus): bool
    {
        $stockItemData = $this->getStockItemData->execute($sku, Stock::DEFAULT_STOCK_ID);
        return $stockItemData !== null
            && (int) $stockItemData[GetStockItemDataInterface::IS_SALABLE] !== $currentStatus;
    }

    /**
     * Returns items stock statuses.
     *
     * @param array $sourceItems
     * @return array
     */
    private function getStockStatuses(array $sourceItems): array
    {
        $skus = [];
        foreach ($sourceItems as $sourceItem) {
            $skus[] = $sourceItem->getSku();
        }

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
}
