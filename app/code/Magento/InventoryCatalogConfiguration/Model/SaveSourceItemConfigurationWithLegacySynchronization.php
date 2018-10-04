<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalogConfiguration\Model;

use Magento\InventoryCatalogApi\Api\DefaultSourceProviderInterface;
use Magento\InventoryConfigurationApi\Api\Data\SourceItemConfigurationInterface;
use Magento\InventoryConfigurationApi\Api\SaveSourceConfigurationInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\InventoryCatalogApi\Model\GetProductIdsBySkusInterface;
use Magento\InventoryCatalogApi\Api\DefaultStockProviderInterface;
use Magento\CatalogInventory\Api\Data\StockItemInterface;

/**
 * @inheritdoc
 */
class SaveSourceItemConfigurationWithLegacySynchronization implements SaveSourceConfigurationInterface
{
    /**
     * @var SaveSourceConfigurationInterface
     */
    private $saveSourceConfiguration;

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
     * @var DefaultSourceProviderInterface
     */
    private $defaultSourceProvider;

    /**
     * SaveSourceItemConfigurationWithLegacySynchronization constructor.
     *
     * @param SaveSourceConfigurationInterface $saveSourceConfiguration
     * @param ResourceConnection $resourceConnection
     * @param GetProductIdsBySkusInterface $getProductIdsBySkus
     * @param DefaultStockProviderInterface $defaultStockProvider
     * @param DefaultSourceProviderInterface $defaultSourceProvider
     */
    public function __construct(
        SaveSourceConfigurationInterface $saveSourceConfiguration,
        ResourceConnection $resourceConnection,
        GetProductIdsBySkusInterface $getProductIdsBySkus,
        DefaultStockProviderInterface $defaultStockProvider,
        DefaultSourceProviderInterface $defaultSourceProvider
    ) {
        $this->saveSourceConfiguration = $saveSourceConfiguration;
        $this->resourceConnection = $resourceConnection;
        $this->getProductIdsBySkus = $getProductIdsBySkus;
        $this->defaultStockProvider = $defaultStockProvider;
        $this->defaultSourceProvider = $defaultSourceProvider;
    }

    /**
     * @inheritdoc
     */
    public function forSourceItem(
        string $sku,
        string $sourceCode,
        SourceItemConfigurationInterface $sourceItemConfiguration
    ): void {
        $connection = $this->resourceConnection->getConnection();
        $sourceConfigurationTable = $this->resourceConnection->getTableName('inventory_source_configuration');

        $columnsSql = $this->buildColumnsSqlPart([
            'sku',
            'source_code',
            SourceItemConfigurationInterface::BACKORDERS,
            SourceItemConfigurationInterface::NOTIFY_STOCK_QTY
        ]);

        $onDuplicateSql = $this->buildOnDuplicateSqlPart([
            SourceItemConfigurationInterface::BACKORDERS,
            SourceItemConfigurationInterface::NOTIFY_STOCK_QTY,
        ]);

        $bind = [
            $sku,
            $sourceCode,
            $sourceItemConfiguration->getBackorders(),
            $sourceItemConfiguration->getNotifyStockQty()
        ];

        $insertSql = sprintf(
            'INSERT INTO %s (%s) VALUES %s %s',
            $sourceConfigurationTable,
            $columnsSql,
            '(?, ?, ?, ?)',
            $onDuplicateSql
        );

        $connection->query($insertSql, $bind);

        if ($sourceCode === $this->defaultSourceProvider->getCode()) {
            $this->updateLegacyStockItem($sku, $sourceItemConfiguration);
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
     * Update legacy stock item
     *
     * @param string $sku
     * @param SourceItemConfigurationInterface $sourceItemConfiguration
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function updateLegacyStockItem(
        string $sku,
        SourceItemConfigurationInterface $sourceItemConfiguration
    ): void {
        $connection = $this->resourceConnection->getConnection();
        $productId = $this->getProductIdsBySkus->execute([$sku])[$sku];

        if ($sourceItemConfiguration->getBackorders() === null) {
            $data[StockItemInterface::USE_CONFIG_BACKORDERS] = 1;
            $data[StockItemInterface::BACKORDERS] = null;
        } else {
            $data[StockItemInterface::USE_CONFIG_BACKORDERS] = 0;
            $data[StockItemInterface::BACKORDERS] = $sourceItemConfiguration->getBackorders();
        }

        if ($sourceItemConfiguration->getNotifyStockQty() === null) {
            $data[StockItemInterface::USE_CONFIG_NOTIFY_STOCK_QTY] = 1;
            $data[StockItemInterface::NOTIFY_STOCK_QTY] = null;
        } else {
            $data[StockItemInterface::USE_CONFIG_NOTIFY_STOCK_QTY] = 0;
            $data[StockItemInterface::NOTIFY_STOCK_QTY] = $sourceItemConfiguration->getNotifyStockQty();
        }

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
    public function forSource(string $sourceCode, SourceItemConfigurationInterface $sourceItemConfiguration): void
    {
        $this->saveSourceConfiguration->forSource($sourceCode, $sourceItemConfiguration);
    }

    /**
     * @inheritdoc
     */
    public function forGlobal(SourceItemConfigurationInterface $sourceItemConfiguration): void
    {
        $this->saveSourceConfiguration->forGlobal($sourceItemConfiguration);
    }
}
