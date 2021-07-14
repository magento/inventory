<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryAdminUi\Test\Integration\Ui\DataProvider;

use Magento\Framework\ObjectManagerInterface;
use Magento\InventoryAdminUi\Ui\DataProvider\StockDataProvider;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\InventoryApi\Api\Data\StockInterface;
use PHPUnit\Framework\TestCase;

class StockDataProviderTest extends TestCase
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
    }

    /**
     * @magentoDataFixture Magento_InventoryApi::Test/_files/sources.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stocks.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stock_source_links.php
     * @dataProvider listingDataStockDataProvider
     */
    public function testGetDataForListingDataStock($stockName, $assignedSources): void
    {
        /** @var StockDataProvider $stockDataProvider */
        $stockDataProvider = $this->objectManager->create(
            StockDataProvider::class,
            [
                'name' => 'inventory_stock_listing_data_stock',
                'primaryFieldName' => 'stock_id',
                'requestFieldName' => 'id',
            ]
        );
        $data = $stockDataProvider->getData();
        foreach ($data['items'] as $stock) {
            if ($stock['name'] === $stockName) {
                $this->assertEquals($assignedSources, $stock['assigned_sources']);
            }
        }
    }

    /**
     * @return array
     */
    public function listingDataStockDataProvider(): array
    {
        return [[
            'Global-stock',
            [
                ['sourceCode' => 'us-1', 'name' => 'US-source-1'],
                ['sourceCode' => 'eu-disabled', 'name' => 'EU-source-disabled'],
                ['sourceCode' => 'eu-3', 'name' => 'EU-source-3'],
                ['sourceCode' => 'eu-2', 'name' => 'EU-source-2'],
                ['sourceCode' => 'eu-1', 'name' => 'EU-source-1'],
            ]
        ]];
    }

    /**
     * @magentoDataFixture Magento_InventoryApi::Test/_files/sources.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stocks.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stock_source_links.php
     */
    public function testGetDataFormDataSource(): void
    {
        /** @var StockDataProvider $stockDataProvider */
        $stockDataProvider = $this->objectManager->create(
            StockDataProvider::class,
            [
                'name' => 'inventory_stock_form_data_source',
                'primaryFieldName' => 'stock_id',
                'requestFieldName' => 'stock_id',
            ]
        );
        $data = $stockDataProvider->getData();
        $data = array_pop($data);
        $this->assertEquals(
            'Default Stock',
            $data['general']['name']
        );
        $this->assertEquals(
            'default',
            $data['sources']['assigned_sources'][0]['source_code']
        );
    }
}
