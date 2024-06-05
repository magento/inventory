<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Model;

use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Model\Indexer\Stock\Processor;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Validation\ValidationException;
use Magento\InventoryApi\Api\GetSourceItemsBySkuInterface;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;
use Magento\InventoryApi\Model\GetSourceCodesBySkusInterface;
use Magento\InventoryCatalog\Model\ResourceModel\UpdateLegacyStockItems;
use Magento\InventoryCatalog\Model\UpdateInventory\InventoryData;
use Magento\InventoryCatalogApi\Model\CompositeProductStockStatusProcessorInterface;
use Magento\InventoryCatalogApi\Model\GetProductIdsBySkusInterface;
use Magento\InventoryCatalogApi\Model\IsSingleSourceModeInterface;
use Magento\InventoryIndexer\Indexer\SourceItem\GetSourceItemIds;
use Magento\InventoryIndexer\Indexer\SourceItem\SourceItemIndexer;
use Magento\InventoryIndexer\Model\ProductSalabilityChangeProcessorInterface;
use Psr\Log\LoggerInterface;

/**
 * Asynchronous inventory update service.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class UpdateInventory
{
    /**
     * @var Processor
     */
    private $stockIndexerProcessor;

    /**
     * @var UpdateLegacyStockItems
     */
    private $updateLegacyStockItems;

    /**
     * @var GetProductIdsBySkusInterface
     */
    private $getProductIdsBySkus;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var GetDefaultSourceItemBySku
     */
    private $getDefaultSourceItemBySku;

    /**
     * @var SourceItemsSaveInterface
     */
    private $sourceItemsSave;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var GetSourceCodesBySkusInterface
     */
    private $getSourceItemsBySku;

    /**
     * @var SourceItemIndexer
     */
    private $sourceItemIndexer;

    /**
     * @var GetSourceItemIds
     */
    private $getSourceItemIds;

    /**
     * @var UpdateDefaultStock
     */
    private $updateDefaultStock;

    /**
     * @var IsSingleSourceModeInterface
     */
    private $isSingleSourceMode;

    /**
     * @var CompositeProductStockStatusProcessorInterface
     */
    private $compositeProductStockStatusProcessor;

    /**
     * @var ProductSalabilityChangeProcessorInterface
     */
    private $productSalabilityChangeProcessor;

    /**
     * @param Processor $stockIndexerProcessor
     * @param UpdateLegacyStockItems $updateLegacyStockItems
     * @param GetProductIdsBySkusInterface $getProductIdsBySkus
     * @param GetDefaultSourceItemBySku $getDefaultSourceItemBySku
     * @param SourceItemsSaveInterface $sourceItemsSave
     * @param GetSourceItemsBySkuInterface $getSourceItemsBySku
     * @param GetSourceItemIds $getSourceItemIds
     * @param SourceItemIndexer $sourceItemIndexer
     * @param SerializerInterface $serializer
     * @param LoggerInterface $logger
     * @param UpdateDefaultStock|null $updateDefaultStock
     * @param IsSingleSourceModeInterface|null $isSingleSourceMode
     * @param CompositeProductStockStatusProcessorInterface|null $compositeProductStockStatusProcessor
     * @param ProductSalabilityChangeProcessorInterface|null $productSalabilityChangeProcessor
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        Processor $stockIndexerProcessor,
        UpdateLegacyStockItems $updateLegacyStockItems,
        GetProductIdsBySkusInterface $getProductIdsBySkus,
        GetDefaultSourceItemBySku $getDefaultSourceItemBySku,
        SourceItemsSaveInterface $sourceItemsSave,
        GetSourceItemsBySkuInterface $getSourceItemsBySku,
        GetSourceItemIds $getSourceItemIds,
        SourceItemIndexer $sourceItemIndexer,
        SerializerInterface $serializer,
        LoggerInterface $logger,
        ?UpdateDefaultStock $updateDefaultStock = null,
        ?IsSingleSourceModeInterface $isSingleSourceMode = null,
        ?CompositeProductStockStatusProcessorInterface $compositeProductStockStatusProcessor = null,
        ?ProductSalabilityChangeProcessorInterface $productSalabilityChangeProcessor = null
    ) {
        $this->stockIndexerProcessor = $stockIndexerProcessor;
        $this->updateLegacyStockItems = $updateLegacyStockItems;
        $this->getProductIdsBySkus = $getProductIdsBySkus;
        $this->getDefaultSourceItemBySku = $getDefaultSourceItemBySku;
        $this->sourceItemsSave = $sourceItemsSave;
        $this->serializer = $serializer;
        $this->logger = $logger;
        $this->getSourceItemsBySku = $getSourceItemsBySku;
        $this->sourceItemIndexer = $sourceItemIndexer;
        $this->getSourceItemIds = $getSourceItemIds;
        $this->updateDefaultStock = $updateDefaultStock
            ?? ObjectManager::getInstance()->get(UpdateDefaultStock::class);
        $this->isSingleSourceMode = $isSingleSourceMode
            ?? ObjectManager::getInstance()->get(IsSingleSourceModeInterface::class);
        $this->compositeProductStockStatusProcessor = $compositeProductStockStatusProcessor
            ?? ObjectManager::getInstance()->get(CompositeProductStockStatusProcessorInterface::class);
        $this->productSalabilityChangeProcessor = $productSalabilityChangeProcessor
            ?? ObjectManager::getInstance()->get(ProductSalabilityChangeProcessorInterface::class);
    }

    /**
     * Update legacy stock items, default source items and reindex inventory for given product skus.
     *
     * @param InventoryData $data
     * @return void
     */
    public function execute(InventoryData $data): void
    {
        $skus = $data->getSkus();
        try {
            $productIds = $this->getProductIdsBySkus->execute($skus);
        } catch (NoSuchEntityException $e) {
            $productIds = [];
        }
        $inventoryData = $this->serializer->unserialize($data->getData());
        $this->updateLegacyStockItems->execute($productIds, $inventoryData);
        if ($this->isSingleSourceMode->execute()) {
            $this->compositeProductStockStatusProcessor->execute($skus);
        }
        $sourceItems = $this->getDefaultSourceItems($skus, $inventoryData);
        if ($sourceItems) {
            try {
                $this->sourceItemsSave->execute($sourceItems);
            } catch (CouldNotSaveException|InputException|ValidationException $e) {
                $this->logger->error($e->getLogMessage());
            }
        }
        $affectedSkus = $this->updateDefaultStock->execute($skus);
        if ($affectedSkus) {
            $this->productSalabilityChangeProcessor->execute($affectedSkus);
        }
        $this->reindexSourceItems($skus);
    }

    /**
     * Get default source items for given product skus.
     *
     * @param array $skus
     * @param array $inventoryData
     * @return array
     */
    private function getDefaultSourceItems(array $skus, array $inventoryData): array
    {
        $sourceItems = [];
        foreach ($skus as $sku) {
            $sourceItem = $this->getDefaultSourceItemBySku->execute($sku);
            if ($sourceItem) {
                $qty = $inventoryData[StockItemInterface::QTY] ?? $sourceItem->getQuantity();
                $status = $inventoryData[StockItemInterface::IS_IN_STOCK] ?? $sourceItem->getStatus();
                $sourceItem->setQuantity((float)$qty);
                $sourceItem->setStatus((int)$status);
                $sourceItems[] = $sourceItem;
            }
        }

        return $sourceItems;
    }

    /**
     * Reindex non-default source items.
     *
     * @param array $skus
     */
    private function reindexSourceItems(array $skus): void
    {
        $sourceItems = [[]];
        foreach ($skus as $sku) {
            $sourceItems[] = $this->getSourceItemsBySku->execute($sku);
        }
        $sourceItems = array_merge(...$sourceItems);
        $sourceItemsIds = $this->getSourceItemIds->execute($sourceItems);
        $this->sourceItemIndexer->executeList($sourceItemsIds);
    }
}
