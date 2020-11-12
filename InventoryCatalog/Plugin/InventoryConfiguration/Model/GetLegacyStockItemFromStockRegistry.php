<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Plugin\InventoryConfiguration\Model;

use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\CatalogInventory\Model\StockRegistryStorage;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryCatalogApi\Model\GetProductIdsBySkusInterface;
use Magento\InventoryConfiguration\Model\GetLegacyStockItem;

/**
 * Retrieve legacy stock item from stock registry
 */
class GetLegacyStockItemFromStockRegistry
{
    /**
     * @var StockConfigurationInterface
     */
    private $stockConfiguration;

    /**
     * @var StockRegistryStorage
     */
    private $stockRegistryStorage;

    /**
     * @var GetProductIdsBySkusInterface
     */
    private $getProductIdsBySkus;

    /**
     * @param StockConfigurationInterface $stockConfiguration
     * @param StockRegistryStorage $stockRegistryStorage
     * @param GetProductIdsBySkusInterface $getProductIdsBySkus
     */
    public function __construct(
        StockConfigurationInterface $stockConfiguration,
        StockRegistryStorage $stockRegistryStorage,
        GetProductIdsBySkusInterface $getProductIdsBySkus
    ) {
        $this->stockConfiguration = $stockConfiguration;
        $this->stockRegistryStorage = $stockRegistryStorage;
        $this->getProductIdsBySkus = $getProductIdsBySkus;
    }

    /**
     * Retrieve legacy stock item from stock registry
     *
     * @param GetLegacyStockItem $subject
     * @param callable $proceed
     * @param string $sku
     * @return StockItemInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundExecute(GetLegacyStockItem $subject, callable $proceed, string $sku): StockItemInterface
    {
        try {
            $productId = $this->getProductIdsBySkus->execute([$sku])[$sku];
            $stockItem = $this->stockRegistryStorage->getStockItem(
                $productId,
                $this->stockConfiguration->getDefaultScopeId()
            );
        } catch (NoSuchEntityException $skuNotFoundInCatalog) {
            $stockItem = null;
        }
        if ($stockItem === null) {
            $stockItem = $proceed($sku);
        }

        return $stockItem;
    }
}
