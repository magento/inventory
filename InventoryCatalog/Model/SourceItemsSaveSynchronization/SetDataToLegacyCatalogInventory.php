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
        $skus = array_map(fn ($sourceItem) => $sourceItem->getSku(), $sourceItems);
        $legacyStockItemsByProductId = [];
        foreach ($this->getLegacyStockItems->execute($skus) as $legacyStockItem) {
            $legacyStockItemsByProductId[$legacyStockItem->getProductId()] = $legacyStockItem;
        }
        foreach ($sourceItems as $sourceItem) {
            $sku = $sourceItem->getSku();
            try {
                $productId = (int)$this->getProductIdsBySkus->execute([$sku])[$sku];
            } catch (NoSuchEntityException $e) {
                // Skip synchronization of for not existed product
                continue;
            }
            $legacyStockItem = $legacyStockItemsByProductId[$productId] ?? null;
            if (null === $legacyStockItem) {
                continue;
            }
            $legacyStockItem->setQty((float)$sourceItem->getQuantity());
            $legacyStockItem->setIsInStock((int)$sourceItem->getStatus());
            if ($legacyStockItem->getManageStock() && !$this->stockStateProvider->verifyStock($legacyStockItem)) {
                $legacyStockItem->setIsInStock(0);
            }
            $this->setDataToLegacyStockItem->execute(
                (string)$sourceItem->getSku(),
                (float)$legacyStockItem->getQty(),
                (int) $legacyStockItem->getIsInStock()
            );
        }
        $affectedSkus = $this->updateDefaultStock->execute($skus);
        if ($affectedSkus) {
            $this->productSalabilityChangeProcessor->execute($affectedSkus);
        }
    }
}
