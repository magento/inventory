<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Model\SourceItemsSaveSynchronization;

use Magento\CatalogInventory\Model\Spi\StockStateProviderInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryCatalog\Model\ResourceModel\SetDataToLegacyStockItem;
use Magento\InventoryCatalog\Model\UpdateDefaultStock;
use Magento\InventoryCatalogApi\Model\GetProductIdsBySkusInterface;
use Magento\InventoryConfiguration\Model\GetLegacyStockItemsInterface;
use Magento\InventoryIndexer\Model\ProductSalabilityChangeProcessorInterface;

/**
 * Set Qty and status for legacy CatalogInventory Stock Information tables.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SetDataToLegacyCatalogInventory
{
    /**
     * @param SetDataToLegacyStockItem $setDataToLegacyStockItem
     * @param GetProductIdsBySkusInterface $getProductIdsBySkus
     * @param StockStateProviderInterface $stockStateProvider
     * @param GetLegacyStockItemsInterface $getLegacyStockItems
     * @param UpdateDefaultStock $updateDefaultStock
     * @param ProductSalabilityChangeProcessorInterface $productSalabilityChangeProcessor
     */
    public function __construct(
        private readonly SetDataToLegacyStockItem $setDataToLegacyStockItem,
        private readonly GetProductIdsBySkusInterface $getProductIdsBySkus,
        private readonly StockStateProviderInterface $stockStateProvider,
        private readonly GetLegacyStockItemsInterface $getLegacyStockItems,
        private readonly UpdateDefaultStock $updateDefaultStock,
        private readonly ProductSalabilityChangeProcessorInterface $productSalabilityChangeProcessor
    ) {
    }

    /**
     * Updates Stock information in legacy inventory.
     *
     * @param array $sourceItems
     */
    public function execute(array $sourceItems): void
    {
        $skus = array_map(fn($sourceItem) => $sourceItem->getSku(), $sourceItems);
        $legacyStockItemsByProductId = [];

        // Fetch legacy stock items for the SKUs
        foreach ($this->getLegacyStockItems->execute($skus) as $legacyStockItem) {
            $legacyStockItemsByProductId[$legacyStockItem->getProductId()] = $legacyStockItem;
        }

        foreach ($sourceItems as $sourceItem) {
            $sku = $sourceItem->getSku();
            try {
                $productId = (int)$this->getProductIdsBySkus->execute([$sku])[$sku];
            } catch (NoSuchEntityException $e) {
                // Skip synchronization for non-existent product
                continue;
            }

            $legacyStockItem = $legacyStockItemsByProductId[$productId] ?? null;
            if (null === $legacyStockItem) {
                continue;
            }

            // Update quantity and stock status based on source item
            $legacyStockItem->setQty((float)$sourceItem->getQuantity());

            // Custom logic: If quantity is 0, set the stock status to 'in stock' or 'out of stock' as per requirements
            if ($legacyStockItem->getQty() == 0) {
                // Set the product as 'out of stock' or 'in stock' based on your logic
                $legacyStockItem->setIsInStock(0);  // 'out of stock'
            } else {
                $legacyStockItem->setIsInStock((int)$sourceItem->getStatus());
            }

            // Check if stock management is enabled and update stock status accordingly
            if ($legacyStockItem->getManageStock() && !$this->stockStateProvider->verifyStock($legacyStockItem)) {
                $legacyStockItem->setIsInStock(0);
            }

            // Execute the update to legacy stock item
            $this->setDataToLegacyStockItem->execute(
                (string)$sourceItem->getSku(),
                (float)$legacyStockItem->getQty(),
                (int)$legacyStockItem->getIsInStock()
            );
        }

        // Update default stock and salability if needed
        $affectedSkus = $this->updateDefaultStock->execute($skus);
        if ($affectedSkus) {
            $this->productSalabilityChangeProcessor->execute($affectedSkus);
        }
    }
}
