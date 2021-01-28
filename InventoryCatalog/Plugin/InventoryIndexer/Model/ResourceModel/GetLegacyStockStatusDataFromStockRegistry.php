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
use Magento\InventoryIndexer\Model\ResourceModel\GetStockItemData;
use Magento\InventorySalesApi\Model\GetStockItemDataInterface;

/**
 * Retrieve legacy stock item data from stock registry
 */
class GetLegacyStockStatusDataFromStockRegistry
{
    /**
     * @var GetStockItemData
     */
    private $getStockItemData;

    /**
     * @var StockConfigurationInterface
     */
    private $stockConfiguration;

    /**
     * @var GetProductIdsBySkusInterface
     */
    private $getProductIdsBySkus;

    /**
     * @var DefaultStockProviderInterface
     */
    private $defaultStockProvider;

    /**
     * @var LegacyStockStatusStorage
     */
    private $legacyStockStatusStorage;

    /**
     * @param GetStockItemData $getStockItemData
     * @param StockConfigurationInterface $stockConfiguration
     * @param LegacyStockStatusStorage $legacyStockStatusStorage
     * @param GetProductIdsBySkusInterface $getProductIdsBySkus
     * @param DefaultStockProviderInterface $defaultStockProvider
     */
    public function __construct(
        GetStockItemData $getStockItemData,
        StockConfigurationInterface $stockConfiguration,
        LegacyStockStatusStorage $legacyStockStatusStorage,
        GetProductIdsBySkusInterface $getProductIdsBySkus,
        DefaultStockProviderInterface $defaultStockProvider
    ) {
        $this->getStockItemData = $getStockItemData;
        $this->stockConfiguration = $stockConfiguration;
        $this->legacyStockStatusStorage = $legacyStockStatusStorage;
        $this->getProductIdsBySkus = $getProductIdsBySkus;
        $this->defaultStockProvider = $defaultStockProvider;
    }

    /**
     * Retrieve legacy stock item data from stock registry
     *
     * @param GetStockItemData $subject
     * @param callable $proceed
     * @param string $sku
     * @param int $stockId
     * @return array|null
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundExecute(GetStockItemData $subject, callable $proceed, string $sku, int $stockId): ?array
    {
        $stockItem = null;
        if ($this->defaultStockProvider->getId() === $stockId) {
            try {
                $productId = $this->getProductIdsBySkus->execute([$sku])[$sku];
                $stockItem = $this->legacyStockStatusStorage->get(
                    (int) $productId,
                    $this->stockConfiguration->getDefaultScopeId()
                );
            } catch (NoSuchEntityException $skuNotFoundInCatalog) {
                $stockItem = null;
            }
        }
        if ($stockItem !== null) {
            $result = [
                GetStockItemDataInterface::QUANTITY => $stockItem->getQty(),
                GetStockItemDataInterface::IS_SALABLE => $stockItem->getStockStatus(),
            ];
        } else {
            $result = $proceed($sku, $stockId);
        }

        return $result;
    }
}
