<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Model\SourceItemsSaveSynchronization;

use Magento\Catalog\Model\Indexer\Product\Price\Processor as PriceIndexProcessor;
use Magento\CatalogInventory\Api\StockItemCriteriaInterfaceFactory;
use Magento\CatalogInventory\Api\StockItemRepositoryInterface;
use Magento\CatalogInventory\Model\Indexer\Stock\CacheCleaner;
use Magento\CatalogInventory\Model\Indexer\Stock\Processor;
use Magento\CatalogInventory\Model\Spi\StockStateProviderInterface;
use Magento\CatalogInventory\Model\Stock;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryCatalog\Model\ResourceModel\SetDataToLegacyStockItem;
use Magento\InventoryCatalog\Model\ResourceModel\SetDataToLegacyStockStatus;
use Magento\InventoryCatalogApi\Model\GetProductIdsBySkusInterface;
use Magento\InventoryConfiguration\Model\GetLegacyStockItems;
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
     * @var GetLegacyStockItems
     */
    private $getLegacyStockItems;

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
     * @param GetLegacyStockItems|null $getLegacyStockItems
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
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
        ?PriceIndexProcessor $priceIndexProcessor = null,
        ?GetLegacyStockItems $getLegacyStockItems = null
    ) {
        $this->setDataToLegacyStockItem = $setDataToLegacyStockItem;
        $this->setDataToLegacyStockStatus = $setDataToLegacyStockStatus;
        $this->getProductIdsBySkus = $getProductIdsBySkus;
        $this->stockStateProvider = $stockStateProvider;
        $this->indexerProcessor = $indexerProcessor;
        $this->areProductsSalable = $areProductsSalable;
        $this->cacheCleaner = $cacheCleaner ?? ObjectManager::getInstance()->get(CacheCleaner::class);
        $this->getStockItemData = $getStockItemData
            ?: ObjectManager::getInstance()->get(GetStockItemDataInterface::class);
        $this->priceIndexProcessor = $priceIndexProcessor
            ?: ObjectManager::getInstance()->get(PriceIndexProcessor::class);
        $this->getLegacyStockItems = $getLegacyStockItems
            ?: ObjectManager::getInstance()->get(GetLegacyStockItems::class);
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
        $skus = [];
        foreach ($sourceItems as $sourceItem) {
            $skus[] = $sourceItem->getSku();
        }
        $productIdsBySkus = $this->getProductIdsBySkus->execute($skus);
        $legacyStockItemsByProductId = [];
        $legacyStockItems = $this->getLegacyStockItems->execute($skus);
        $stockStatuses = $this->getStockStatuses($sourceItems);
        foreach ($legacyStockItems as $legacyStockItem) {
            $legacyStockItemsByProductId[$legacyStockItem->getProductId()] = $legacyStockItem;
        }
        $productIds = [];
        $productIdsForPriceReindex = [];
        $this->updateSourceItemsInnerLoop(
            $sourceItems,
            $productIdsBySkus,
            $stockStatuses,
            $legacyStockItemsByProductId,
            $productIdsForPriceReindex,
            $productIds
        );
        if ($productIds) {
            $this->indexerProcessor->reindexList($productIds);
        }
        if ($productIdsForPriceReindex) {
            $this->priceIndexProcessor->reindexList($productIdsForPriceReindex);
        }
    }

    /**
     * Inner loop of updateSourceItems
     *
     * @param array $sourceItems
     * @param array $productIdsBySkus
     * @param array $stockStatuses
     * @param array $legacyStockItemsByProductId
     * @param array $productIdsForPriceReindex
     * @param array $productIds
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function updateSourceItemsInnerLoop(
        array $sourceItems,
        array $productIdsBySkus,
        array $stockStatuses,
        array $legacyStockItemsByProductId,
        array &$productIdsForPriceReindex,
        array &$productIds
    ): void {
        foreach ($sourceItems as $sourceItem) {
            $sku = $sourceItem->getSku();
            $productId = $productIdsBySkus[$sku] ?? null;
            $legacyStockItem = $legacyStockItemsByProductId[$productId] ?? null;
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
}
