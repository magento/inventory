<?php
/**
 * Copyright 2024 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Plugin\CatalogInventory\Model\Stock\StockItemRepository;

use Magento\Catalog\Model\Indexer\Product\Full as FullProductIndexer;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Model\Stock\StockItemRepository;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Inventory\Model\SourceItem\Command\GetSourceItemsBySku;
use Magento\InventoryCatalogApi\Model\GetSkusByProductIdsInterface;
use Magento\InventoryIndexer\Indexer\InventoryIndexer;

class StockItemRepositoryPlugin
{

    /**
     * @param FullProductIndexer $fullProductIndexer
     * @param InventoryIndexer $inventoryIndexer
     * @param GetSkusByProductIdsInterface $getSkusByProductIds
     * @param GetSourceItemsBySku $getSourceItemsBySku
     */
    public function __construct(
        private readonly FullProductIndexer $fullProductIndexer,
        private readonly InventoryIndexer $inventoryIndexer,
        private readonly GetSkusByProductIdsInterface $getSkusByProductIds,
        private readonly GetSourceItemsBySku $getSourceItemsBySku
    ) {
    }

    /**
     * Complex reindex after product stock item has been saved.
     *
     * @param StockItemRepository $subject
     * @param StockItemInterface $stockItem
     * @return StockItemInterface
     * @throws NoSuchEntityException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterSave(StockItemRepository $subject, StockItemInterface $stockItem): StockItemInterface
    {
        $productSku = $this->getSkusByProductIds->execute([$stockItem->getProductId()])[$stockItem->getProductId()];
        $this->fullProductIndexer->executeRow($stockItem->getProductId());
        $sourceItems = $this->getSourceItemsBySku->execute($productSku);
        $sourceItemIds = [];

        foreach ($sourceItems as $sourceItem) {
            $sourceItemIds[] = $sourceItem->getId();
        }
        $this->inventoryIndexer->executeList($sourceItemIds);
        return $stockItem;
    }
}
