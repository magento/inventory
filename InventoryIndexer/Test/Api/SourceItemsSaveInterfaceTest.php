<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Test\Api;

use Magento\CatalogInventory\Model\Stock;
use Magento\Framework\Webapi\Rest\Request;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryIndexer\Model\ResourceModel\GetStockItemData;
use Magento\InventorySalesApi\Model\GetStockItemDataInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\WebapiAbstract;

class SourceItemsSaveInterfaceTest extends WebapiAbstract
{
    private const RESOURCE_PATH = '/V1/inventory/source-items';
    private const SERVICE_NAME_SAVE = 'inventoryApiSourceItemsSaveV1';

    /**
     * @var GetStockItemData
     */
    private $getStockItemData;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->getStockItemData = Bootstrap::getObjectManager()->get(GetStockItemData::class);
    }

    /**
     * @magentoApiDataFixture Magento/Catalog/_files/products.php
     * @magentoApiDataFixture Magento_InventoryApi::Test/_files/stock_with_source_link.php
     * @magentoApiDataFixture Magento/Catalog/_files/reindex_catalog_inventory_stock.php
     * @magentoApiDataFixture Magento_InventoryIndexer::Test/_files/reindex_inventory_rollback.php
     */
    public function testProductSalabilityShouldChangeAfterUpdatingSourceItemDefaultStock(): void
    {
        $sku = 'simple';
        $sourceCode = 'default';
        $stockId = Stock::DEFAULT_STOCK_ID;
        $stockData = $this->getStockItemData->execute($sku, $stockId);
        $this->assertTrue((bool)$stockData[GetStockItemDataInterface::IS_SALABLE]);
        $this->saveSourceItems(
            [
                [
                    SourceItemInterface::SOURCE_CODE => $sourceCode,
                    SourceItemInterface::SKU => $sku,
                    SourceItemInterface::QUANTITY => 0,
                    SourceItemInterface::STATUS => SourceItemInterface::STATUS_IN_STOCK,
                ],
            ]
        );
        $stockData = $this->getStockItemData->execute($sku, $stockId);
        $this->assertFalse((bool)$stockData[GetStockItemDataInterface::IS_SALABLE]);
        $this->saveSourceItems(
            [
                [
                    SourceItemInterface::SOURCE_CODE => $sourceCode,
                    SourceItemInterface::SKU => $sku,
                    SourceItemInterface::QUANTITY => 10,
                    SourceItemInterface::STATUS => SourceItemInterface::STATUS_IN_STOCK,
                ],
            ]
        );
        $stockData = $this->getStockItemData->execute($sku, $stockId);
        $this->assertTrue((bool)$stockData[GetStockItemDataInterface::IS_SALABLE]);
    }

    /**
     * @magentoApiDataFixture Magento_InventorySalesApi::Test/_files/websites_with_stores.php
     * @magentoApiDataFixture Magento_InventoryApi::Test/_files/sources.php
     * @magentoApiDataFixture Magento_InventoryApi::Test/_files/stocks.php
     * @magentoApiDataFixture Magento_InventoryApi::Test/_files/stock_source_links.php
     * @magentoApiDataFixture Magento_InventorySalesApi::Test/_files/stock_website_sales_channels.php
     * @magentoApiDataFixture Magento_InventoryApi::Test/_files/products.php
     * @magentoApiDataFixture Magento_InventoryApi::Test/_files/source_items.php
     * @magentoApiDataFixture Magento_InventoryApi::Test/_files/assign_products_to_websites.php
     * @magentoApiDataFixture Magento_InventoryIndexer::Test/_files/reindex_inventory.php
     */
    public function testProductSalabilityShouldChangeAfterUpdatingSourceItemCustomStock(): void
    {
        $sku = 'SKU-2';
        $sourceCode = 'us-1';
        $stockId = 20;
        $stockData = $this->getStockItemData->execute($sku, $stockId);
        $this->assertTrue((bool)$stockData[GetStockItemDataInterface::IS_SALABLE]);
        $this->saveSourceItems(
            [
                [
                    SourceItemInterface::SOURCE_CODE => $sourceCode,
                    SourceItemInterface::SKU => $sku,
                    SourceItemInterface::QUANTITY => 0,
                    SourceItemInterface::STATUS => SourceItemInterface::STATUS_IN_STOCK,
                ],
            ]
        );
        $stockData = $this->getStockItemData->execute($sku, $stockId);
        $this->assertFalse((bool)$stockData[GetStockItemDataInterface::IS_SALABLE]);
        $this->saveSourceItems(
            [
                [
                    SourceItemInterface::SOURCE_CODE => $sourceCode,
                    SourceItemInterface::SKU => $sku,
                    SourceItemInterface::QUANTITY => 10,
                    SourceItemInterface::STATUS => SourceItemInterface::STATUS_IN_STOCK,
                ],
            ]
        );
        $stockData = $this->getStockItemData->execute($sku, $stockId);
        $this->assertTrue((bool)$stockData[GetStockItemDataInterface::IS_SALABLE]);
    }

    /**
     * @param array $sourceItems
     * @return void
     */
    private function saveSourceItems(array $sourceItems): void
    {
        $serviceInfo = [
            'rest' => [
                'resourcePath' => self::RESOURCE_PATH,
                'httpMethod' => Request::HTTP_METHOD_POST,
            ],
            'soap' => [
                'service' => self::SERVICE_NAME_SAVE,
                'operation' => self::SERVICE_NAME_SAVE . 'Execute',
            ],
        ];
        $this->_webApiCall($serviceInfo, ['sourceItems' => $sourceItems]);
    }
}
