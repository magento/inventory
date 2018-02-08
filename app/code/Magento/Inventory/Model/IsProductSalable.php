<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Inventory\Model;

use Magento\Catalog\Model\ResourceModel\Product as ProductResourceModel;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Api\StockItemCriteriaInterfaceFactory;
use Magento\CatalogInventory\Model\Configuration;
use Magento\CatalogInventory\Model\Stock\Item as LegacyStockItem;
use Magento\CatalogInventory\Model\Stock\StockItemRepository as LegacyStockItemRepository;
use Magento\InventoryApi\Api\IsProductSalableInterface;
use Magento\InventoryConfiguration\Model\StockConfigurationInterface;

/**
 * @inheritdoc
 */
class IsProductSalable implements IsProductSalableInterface
{
    /**
     * @var GetStockItemDataInterface
     */
    private $getStockItemData;

    /**
     * @var GetReservationsQuantityInterface
     */
    private $getReservationsQuantity;

    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * @var LegacyStockItemRepository
     */
    private $legacyStockItemRepository;

    /**
     * @var ProductResourceModel
     */
    private $productResource;

    /**
     * @var StockItemCriteriaInterfaceFactory
     */
    private $stockItemCriteriaFactory;

    /**
     * @var StockConfigurationInterface
     */
    private $stockConfiguration;

    /**
     * IsProductSalable constructor.
     * @param GetStockItemDataInterface $getStockItemData
     * @param GetReservationsQuantityInterface $getReservationsQuantity
     * @param Configuration $configuration
     * @param LegacyStockItemRepository $legacyStockItemRepository
     * @param ProductResourceModel $productResource
     * @param StockItemCriteriaInterfaceFactory $stockItemCriteriaFactory
     * @param StockConfigurationInterface $stockConfiguration
     */
    public function __construct(
        GetStockItemDataInterface $getStockItemData,
        GetReservationsQuantityInterface $getReservationsQuantity,
        Configuration $configuration,
        LegacyStockItemRepository $legacyStockItemRepository,
        ProductResourceModel $productResource,
        StockItemCriteriaInterfaceFactory $stockItemCriteriaFactory,
        StockConfigurationInterface $stockConfiguration
    ) {
        $this->getStockItemData = $getStockItemData;
        $this->getReservationsQuantity = $getReservationsQuantity;
        $this->configuration = $configuration;
        $this->legacyStockItemRepository = $legacyStockItemRepository;
        $this->productResource = $productResource;
        $this->stockItemCriteriaFactory = $stockItemCriteriaFactory;
        $this->stockConfiguration = $stockConfiguration;
    }

    /**
     * @inheritdoc
     */
    public function execute(string $sku, int $stockId): bool
    {
        $stockItemData = $this->getStockItemData->execute($sku, $stockId);
        if (null === $stockItemData) {
            return false;
        }

        $isSalable = (bool)$stockItemData['is_salable'];
        $qtyWithReservation = $stockItemData['quantity'] + $this->getReservationsQuantity->execute($sku, $stockId);
        $globalMinQty = $this->configuration->getMinQty();
        $legacyStockItem = $this->getLegacyStockItem($sku);
        if (null === $legacyStockItem) {
            return false;
        }

        return $this->stockConfiguration->validate($sku, $stockId, $qtyWithReservation, $isSalable, $globalMinQty);
    }
}
