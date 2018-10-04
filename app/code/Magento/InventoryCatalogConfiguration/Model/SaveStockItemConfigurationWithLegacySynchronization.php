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

/**
 * @inheritdoc
 */
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
     * SaveStockItemConfigurationWithLegacySynchronization constructor.
     *
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
        $stockConfigurationTable = $this->resourceConnection->getTableName('inventory_stock_configuration');

        $columnsSql = $this->buildColumnsSqlPart([
            'sku',
            'stock_id',
            StockItemConfigurationInterface::MIN_QTY,
            StockItemConfigurationInterface::MIN_SALE_QTY,
            StockItemConfigurationInterface::MAX_SALE_QTY,
            StockItemConfigurationInterface::ENABLE_QTY_INCREMENTS,
            StockItemConfigurationInterface::QTY_INCREMENTS,
            StockItemConfigurationInterface::MANAGE_STOCK,
            StockItemConfigurationInterface::LOW_STOCK_DATE,
            StockItemConfigurationInterface::IS_QTY_DECIMAL,
            StockItemConfigurationInterface::IS_DECIMAL_DIVIDED,
            StockItemConfigurationInterface::STOCK_STATUS_CHANGED_AUTO,
            StockItemConfigurationInterface::STOCK_THRESHOLD_QTY
        ]);

        $onDuplicateSql = $this->buildOnDuplicateSqlPart([
            StockItemConfigurationInterface::MIN_QTY,
            StockItemConfigurationInterface::MIN_SALE_QTY,
            StockItemConfigurationInterface::MAX_SALE_QTY,
            StockItemConfigurationInterface::ENABLE_QTY_INCREMENTS,
            StockItemConfigurationInterface::QTY_INCREMENTS,
            StockItemConfigurationInterface::MANAGE_STOCK,
            StockItemConfigurationInterface::LOW_STOCK_DATE,
            StockItemConfigurationInterface::IS_QTY_DECIMAL,
            StockItemConfigurationInterface::IS_DECIMAL_DIVIDED,
            StockItemConfigurationInterface::STOCK_STATUS_CHANGED_AUTO,
            StockItemConfigurationInterface::STOCK_THRESHOLD_QTY
        ]);

        $bind = [
            $sku,
            $stockId,
            $stockItemConfiguration->getMinQty(),
            $stockItemConfiguration->getMinSaleQty(),
            $stockItemConfiguration->getMaxSaleQty(),
            $stockItemConfiguration->isEnableQtyIncrements(),
            $stockItemConfiguration->getQtyIncrements(),
            $stockItemConfiguration->isManageStock(),
            $stockItemConfiguration->getLowStockDate(),
            $stockItemConfiguration->isQtyDecimal(),
            $stockItemConfiguration->isDecimalDivided(),
            $stockItemConfiguration->getStockStatusChangedAuto(),
            $stockItemConfiguration->getStockThresholdQty()
        ];

        $insertSql = sprintf(
            'INSERT INTO %s (%s) VALUES %s %s',
            $stockConfigurationTable,
            $columnsSql,
            '(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            $onDuplicateSql
        );

        $connection->query($insertSql, $bind);

        if ($stockId === $this->defaultStockProvider->getId()) {
            $this->updateLegacyStockItem($sku, $stockItemConfiguration);
        }
    }

    /**
     * @param array $columns
     * @return string
     */
    private function buildColumnsSqlPart(array $columns): string
    {
        $connection = $this->resourceConnection->getConnection();
        $processedColumns = array_map([$connection, 'quoteIdentifier'], $columns);
        $sql = implode(', ', $processedColumns);
        return $sql;
    }

    /**
     * @param array $fields
     * @return string
     */
    private function buildOnDuplicateSqlPart(array $fields): string
    {
        $connection = $this->resourceConnection->getConnection();
        $processedFields = [];
        foreach ($fields as $field) {
            $processedFields[] = sprintf('%1$s = VALUES(%1$s)', $connection->quoteIdentifier($field));
        }
        $sql = 'ON DUPLICATE KEY UPDATE ' . implode(', ', $processedFields);
        return $sql;
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
            $this->resourceConnection->getTableName('cataloginventory_stock_item'),
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
