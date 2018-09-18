<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalogConfiguration\Model;

use Magento\InventoryConfigurationApi\Api\Data\StockItemConfigurationInterface;
use Magento\InventoryConfigurationApi\Api\SaveStockConfigurationInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\InventoryCatalogApi\Model\GetProductIdsBySkusInterface;
use Magento\InventoryCatalogApi\Api\DefaultStockProviderInterface;
use Magento\CatalogInventory\Api\Data\StockItemInterface;

class SaveStockItemConfigurationWithLegacySynchronization implements SaveStockConfigurationInterface
{
    /**
     * @var SaveStockConfigurationInterface
     */
    private $saveStockConfiguration;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var GetProductIdsBySkusInterface
     */
    private $getProductIdsBySkus;

    /**
     * @var DefaultStockProviderInterface
     */
    private $defaultStockProvider;

    /**
     * @param SaveStockConfigurationInterface $saveStockConfiguration
     * @param ResourceConnection $resourceConnection
     * @param GetProductIdsBySkusInterface $getProductIdsBySkus
     * @param DefaultStockProviderInterface $defaultStockProvider
     */
    public function __construct(
        SaveStockConfigurationInterface $saveStockConfiguration,
        ResourceConnection $resourceConnection,
        GetProductIdsBySkusInterface $getProductIdsBySkus,
        DefaultStockProviderInterface $defaultStockProvider
    ) {
        $this->saveStockConfiguration = $saveStockConfiguration;
        $this->resourceConnection = $resourceConnection;
        $this->getProductIdsBySkus = $getProductIdsBySkus;
        $this->defaultStockProvider = $defaultStockProvider;
    }

    /**
     * @inheritdoc
     */
    public function forStockItem(
        string $sku,
        int $stockId,
        StockItemConfigurationInterface $stockItemConfiguration
    ): void {
        $connection = $this->resourceConnection->getConnection();
        $stockConfigurationTable = $connection->getTableName('inventory_stock_configuration');

        $data = [
            'sku' => $sku,
            'stock_id' => $stockId,
            StockItemConfigurationInterface::MIN_QTY => $stockItemConfiguration->getMinQty(),
            StockItemConfigurationInterface::MIN_SALE_QTY => $stockItemConfiguration->getMinSaleQty(),
            StockItemConfigurationInterface::MAX_SALE_QTY => $stockItemConfiguration->getMaxSaleQty(),
            StockItemConfigurationInterface::ENABLE_QTY_INCREMENTS => $stockItemConfiguration->isEnableQtyIncrements(),
            StockItemConfigurationInterface::QTY_INCREMENTS => $stockItemConfiguration->getQtyIncrements(),
            StockItemConfigurationInterface::MANAGE_STOCK => $stockItemConfiguration->isManageStock(),
            StockItemConfigurationInterface::LOW_STOCK_DATE => $stockItemConfiguration->getLowStockDate(),
            StockItemConfigurationInterface::IS_QTY_DECIMAL => $stockItemConfiguration->isQtyDecimal(),
            StockItemConfigurationInterface::IS_DECIMAL_DIVIDED => $stockItemConfiguration->isDecimalDivided(),
            StockItemConfigurationInterface::STOCK_STATUS_CHANGED_AUTO =>
                $stockItemConfiguration->getStockStatusChangedAuto(),
            StockItemConfigurationInterface::STOCK_THRESHOLD_QTY => $stockItemConfiguration->getStockThresholdQty(),
        ];
        $connection->insertOnDuplicate(
            $stockConfigurationTable,
            $data
        );

        if ($stockId === $this->defaultStockProvider->getId()) {
            $this->updateLegacyStockItem($sku, $stockItemConfiguration);
        }
    }

    /**
     * @param string $sku
     * @param StockItemConfigurationInterface $stockItemConfiguration
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function updateLegacyStockItem(
        string $sku,
        StockItemConfigurationInterface $stockItemConfiguration
    ): void {
        $connection = $this->resourceConnection->getConnection();
        $productId = $this->getProductIdsBySkus->execute([$sku])[$sku];

        if ($stockItemConfiguration->getMinQty() === null) {
            $data[StockItemInterface::USE_CONFIG_MIN_QTY] = 1;
            $data[StockItemInterface::MIN_QTY] = null;
        } else {
            $data[StockItemInterface::USE_CONFIG_MIN_QTY] = 0;
            $data[StockItemInterface::MIN_QTY] = $stockItemConfiguration->getMinQty();
        }

        if ($stockItemConfiguration->getMinSaleQty() === null) {
            $data[StockItemInterface::USE_CONFIG_MIN_SALE_QTY] = 1;
            $data[StockItemInterface::MIN_SALE_QTY] = null;
        } else {
            $data[StockItemInterface::USE_CONFIG_MIN_SALE_QTY] = 0;
            $data[StockItemInterface::MIN_SALE_QTY] = $stockItemConfiguration->getMinSaleQty();
        }

        if ($stockItemConfiguration->getMaxSaleQty() === null) {
            $data[StockItemInterface::USE_CONFIG_MAX_SALE_QTY] = 1;
            $data[StockItemInterface::MAX_SALE_QTY] = null;
        } else {
            $data[StockItemInterface::USE_CONFIG_MAX_SALE_QTY] = 0;
            $data[StockItemInterface::MAX_SALE_QTY] = $stockItemConfiguration->getMaxSaleQty();
        }

        if ($stockItemConfiguration->isEnableQtyIncrements() === null) {
            $data[StockItemInterface::USE_CONFIG_ENABLE_QTY_INC] = 1;
            $data[StockItemInterface::ENABLE_QTY_INCREMENTS] = null;
        } else {
            $data[StockItemInterface::USE_CONFIG_ENABLE_QTY_INC] = 0;
            $data[StockItemInterface::ENABLE_QTY_INCREMENTS] = $stockItemConfiguration->isEnableQtyIncrements();
        }

        if ($stockItemConfiguration->getQtyIncrements() === null) {
            $data[StockItemInterface::USE_CONFIG_QTY_INCREMENTS] = 1;
            $data[StockItemInterface::QTY_INCREMENTS] = null;
        } else {
            $data[StockItemInterface::USE_CONFIG_QTY_INCREMENTS] = 0;
            $data[StockItemInterface::QTY_INCREMENTS] = $stockItemConfiguration->getQtyIncrements();
        }

        if ($stockItemConfiguration->isManageStock() === null) {
            $data[StockItemInterface::USE_CONFIG_MANAGE_STOCK] = 1;
            $data[StockItemInterface::MANAGE_STOCK] = null;
        } else {
            $data[StockItemInterface::USE_CONFIG_MANAGE_STOCK] = 0;
            $data[StockItemInterface::MANAGE_STOCK] = $stockItemConfiguration->isManageStock();
        }

        $data[StockItemInterface::LOW_STOCK_DATE] = $stockItemConfiguration->getLowStockDate();
        $data[StockItemInterface::IS_DECIMAL_DIVIDED] = $stockItemConfiguration->isDecimalDivided();
        $data[StockItemInterface::IS_QTY_DECIMAL] = $stockItemConfiguration->isQtyDecimal();
        $data[StockItemInterface::STOCK_STATUS_CHANGED_AUTO] = $stockItemConfiguration->getStockStatusChangedAuto();

        $whereCondition[StockItemInterface::STOCK_ID . ' = ?'] = $this->defaultStockProvider->getId();
        $whereCondition[StockItemInterface::PRODUCT_ID . ' = ?'] = $productId;

        $connection->update(
            $connection->getTableName('cataloginventory_stock_item'),
            $data,
            $whereCondition
        );
    }

    /**
     * @inheritdoc
     */
    public function forStock(int $stockId, StockItemConfigurationInterface $stockItemConfiguration): void
    {
        $this->saveStockConfiguration->forStock($stockId, $stockItemConfiguration);
    }

    /**
     * @inheritdoc
     */
    public function forGlobal(StockItemConfigurationInterface $stockItemConfiguration): void
    {
        $this->saveStockConfiguration->forGlobal($stockItemConfiguration);
    }
}
