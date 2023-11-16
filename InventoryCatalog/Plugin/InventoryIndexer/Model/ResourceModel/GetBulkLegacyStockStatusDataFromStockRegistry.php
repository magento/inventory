<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Plugin\InventoryIndexer\Model\ResourceModel;

use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryCatalog\Model\Cache\LegacyStockStatusStorage;
use Magento\InventoryCatalogApi\Api\DefaultStockProviderInterface;
use Magento\InventoryCatalogApi\Model\GetProductIdsBySkusInterface;
use Magento\InventoryIndexer\Model\ResourceModel\GetStockItemsData;
use Magento\InventorySalesApi\Model\GetStockItemDataInterface;

/**
 * Retrieve legacy stock item data from stock registry by bulk operation.
 */
class GetBulkLegacyStockStatusDataFromStockRegistry
{
    /**
     * @var StockConfigurationInterface
     */
    private StockConfigurationInterface $stockConfiguration;

    /**
     * @var GetProductIdsBySkusInterface
     */
    private GetProductIdsBySkusInterface $getProductIdsBySkus;

    /**
     * @var DefaultStockProviderInterface
     */
    private DefaultStockProviderInterface $defaultStockProvider;

    /**
     * @var LegacyStockStatusStorage
     */
    private LegacyStockStatusStorage $legacyStockStatusStorage;

    /**
     * @param StockConfigurationInterface $stockConfiguration
     * @param LegacyStockStatusStorage $legacyStockStatusStorage
     * @param GetProductIdsBySkusInterface $getProductIdsBySkus
     * @param DefaultStockProviderInterface $defaultStockProvider
     */
    public function __construct(
        StockConfigurationInterface $stockConfiguration,
        LegacyStockStatusStorage $legacyStockStatusStorage,
        GetProductIdsBySkusInterface $getProductIdsBySkus,
        DefaultStockProviderInterface $defaultStockProvider
    ) {
        $this->stockConfiguration = $stockConfiguration;
        $this->legacyStockStatusStorage = $legacyStockStatusStorage;
        $this->getProductIdsBySkus = $getProductIdsBySkus;
        $this->defaultStockProvider = $defaultStockProvider;
    }

    /**
     * Retrieve legacy stock item data from stock registry by bulk operation
     *
     * @param GetStockItemsData $subject
     * @param callable $proceed
     * @param array $skus
     * @param int $stockId
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundExecute(GetStockItemsData $subject, callable $proceed, array $skus, int $stockId): array
    {
        $results = [];

        if ($this->defaultStockProvider->getId() === $stockId) {
            try {
                $productIds = $this->getProductIdsBySkus->execute($skus);
            } catch (NoSuchEntityException $e) {
                return $proceed($skus, $stockId);
            }

            foreach ($skus as $sku) {
                $productId = $productIds[$sku] ?? null;

                if ($productId !== null) {
                    $stockItem = $this->legacyStockStatusStorage->get(
                        (int) $productId,
                        $this->stockConfiguration->getDefaultScopeId()
                    );

                    if ($stockItem !== null) {
                        $results[$sku] = [
                            GetStockItemDataInterface::QUANTITY => $stockItem->getQty(),
                            GetStockItemDataInterface::IS_SALABLE => $stockItem->getStockStatus(),
                        ];
                    }
                }
            }
        }

        $originalResults = $proceed($skus, $stockId);

        // Merging custom results with the original method results
        foreach ($skus as $sku) {
            $results[$sku] = $results[$sku] ?? $originalResults[$sku] ?? null;
        }

        return $results;
    }
}
