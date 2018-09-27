<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Setup\Patch\Schema;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryApi\Api\Data\StockInterface;
use Magento\InventoryApi\Api\Data\StockSourceLinkInterface;
use Magento\InventoryCatalog\Model\DefaultSourceProvider;
use Magento\InventoryCatalogApi\Api\DefaultSourceProviderInterface;
use Magento\InventoryCatalogApi\Api\DefaultStockProviderInterface;

/**
 * Patch schema with information about default stock
 */
class InitializeDefaultStock implements SchemaPatchInterface
{
    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @var DefaultSourceProvider
     */
    private $defaultSourceProvider;

    /**
     * @var DefaultStockProviderInterface
     */
    private $defaultStockProvider;

    /**
     * @param ResourceConnection $resource
     * @param DefaultSourceProviderInterface $defaultSourceProvider
     * @param DefaultStockProviderInterface $defaultStockProvider
     */
    public function __construct(
        ResourceConnection $resource,
        DefaultSourceProviderInterface $defaultSourceProvider,
        DefaultStockProviderInterface $defaultStockProvider
    ) {
        $this->resource = $resource;
        $this->defaultSourceProvider = $defaultSourceProvider;
        $this->defaultStockProvider = $defaultStockProvider;
    }

    /**
     * @inheritDoc
     */
    public function apply()
    {
        $this->createDefaultSource();
        $this->createDefaultStock();
        $this->createDefaultSourceStockLink();

        return $this;
    }

    /**
     * @inheritDoc
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * @return void
     */
    private function createDefaultSource(): void
    {
        $connection = $this->resource->getConnection();
        $sourceData = [
            SourceInterface::SOURCE_CODE => $this->defaultSourceProvider->getCode(),
            SourceInterface::NAME => 'Default Source',
            SourceInterface::ENABLED => 1,
            SourceInterface::DESCRIPTION => 'Default Source',
            SourceInterface::LATITUDE => 0,
            SourceInterface::LONGITUDE => 0,
            SourceInterface::COUNTRY_ID => 'US',
            SourceInterface::POSTCODE => '00000',
        ];
        $connection->insert($connection->getTableName('inventory_source'), $sourceData);
    }

    /**
     * @return void
     */
    private function createDefaultStock(): void
    {
        $connection = $this->resource->getConnection();
        $stockData = [
            StockInterface::STOCK_ID => $this->defaultStockProvider->getId(),
            StockInterface::NAME => 'Default Stock',
        ];
        $connection->insert($connection->getTableName('inventory_stock'), $stockData);
    }

    /**
     * @return void
     */
    private function createDefaultSourceStockLink(): void
    {
        $connection = $this->resource->getConnection();
        $stockSourceLinkData = [
            StockSourceLinkInterface::SOURCE_CODE => $this->defaultSourceProvider->getCode(),
            StockSourceLinkInterface::STOCK_ID => $this->defaultStockProvider->getId(),
            StockSourceLinkInterface::PRIORITY => 1,
        ];
        $connection->insert($connection->getTableName('inventory_source_stock_link'), $stockSourceLinkData);
    }
}
