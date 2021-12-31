<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\InventoryCatalog\Plugin\CatalogInventory\Api\StockRegistry;

use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\CatalogInventory\Model\Stock;
use Magento\InventoryCatalog\Model\ResourceModel\SetDataToLegacyStockStatus;
use Magento\InventoryCatalogApi\Model\GetProductTypesBySkusInterface;
use Magento\InventoryConfiguration\Model\LegacyStockItem\CacheStorage;
use Magento\InventoryConfigurationApi\Api\GetStockItemConfigurationInterface;
use Magento\InventoryConfigurationApi\Model\IsSourceItemManagementAllowedForProductTypeInterface;

class AdaptUpdateStockStatusBySkuPlugin
{
    /**
     * @var SetDataToLegacyStockStatus
     */
    private $setDataToLegacyStockStatus;

    /**
     * @var GetProductTypesBySkusInterface
     */
    private $getProductTypesBySkus;

    /**
     * @var IsSourceItemManagementAllowedForProductTypeInterface
     */
    private $isSourceItemManagementAllowedForProductType;

    /**
     * @var GetStockItemConfigurationInterface
     */
    private $getStockItemConfiguration;

    /**
     * @var CacheStorage
     */
    private $legacyStockItemCacheStorage;

    /**
     * @param SetDataToLegacyStockStatus $setDataToLegacyStockStatus
     * @param GetProductTypesBySkusInterface $getProductTypesBySkus
     * @param IsSourceItemManagementAllowedForProductTypeInterface $isSourceItemManagementAllowedForProductType
     * @param GetStockItemConfigurationInterface $getStockItemConfiguration
     * @param CacheStorage $legacyStockItemCacheStorage
     */
    public function __construct(
        SetDataToLegacyStockStatus $setDataToLegacyStockStatus,
        GetProductTypesBySkusInterface $getProductTypesBySkus,
        IsSourceItemManagementAllowedForProductTypeInterface $isSourceItemManagementAllowedForProductType,
        GetStockItemConfigurationInterface $getStockItemConfiguration,
        CacheStorage $legacyStockItemCacheStorage
    ) {
        $this->setDataToLegacyStockStatus = $setDataToLegacyStockStatus;
        $this->getProductTypesBySkus = $getProductTypesBySkus;
        $this->isSourceItemManagementAllowedForProductType = $isSourceItemManagementAllowedForProductType;
        $this->getStockItemConfiguration = $getStockItemConfiguration;
        $this->legacyStockItemCacheStorage = $legacyStockItemCacheStorage;
    }

    /**
     * Replicate stock status information to legacy stock.
     *
     * @param StockRegistryInterface $subject
     * @param int $itemId
     * @param string $productSku
     * @param StockItemInterface $stockItem
     * @return int
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterUpdateStockItemBySku(
        StockRegistryInterface $subject,
        int $itemId,
        string $productSku,
        StockItemInterface $stockItem
    ): int {
        // Remove cache to get updated legacy stock item on the next request.
        $this->legacyStockItemCacheStorage->delete($productSku);

        $productType = $this->getProductTypesBySkus->execute([$productSku])[$productSku];

        $stockItemConfiguration = $this->getStockItemConfiguration->execute($productSku, Stock::DEFAULT_STOCK_ID);
        if ($stockItemConfiguration->isManageStock() === false
            || $stockItemConfiguration->isUseConfigManageStock() === false
        ) {
            $this->setDataToLegacyStockStatus->execute($productSku, (float)$stockItem->getQty(), 1);
        } else {
            if ($this->isSourceItemManagementAllowedForProductType->execute($productType)
                && $stockItem->getQty() !== null
            ) {
                $this->setDataToLegacyStockStatus->execute(
                    $productSku,
                    (float)$stockItem->getQty(),
                    $stockItem->getIsInStock()
                );
            }
        }
        return $itemId;
    }
}
