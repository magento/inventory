<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySalesAdminUi\Test\Integration\Model;

use Magento\InventorySalesAdminUi\Model\GetSalableQuantityDataBySku;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Class to test getting salable quantity data by sku in the admin area
 *
 * @magentoAppArea adminhtml
 * @magentoAppIsolation enabled
 */
class GetSalableQuantityDataBySkuTest extends TestCase
{
    /**
     * @var GetSalableQuantityDataBySku
     */
    private $getSalableQuantityDataBySku;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->getSalableQuantityDataBySku = Bootstrap::getObjectManager()->get(GetSalableQuantityDataBySku::class);
    }

    /**
     * @magentoDataFixture Magento_InventoryApi::Test/_files/products.php
     * @magentoDataFixture Magento_InventoryCatalog::Test/_files/disable_products.php
     * @magentoDataFixture Magento_InventoryCatalog::Test/_files/source_items_on_default_source.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/sources.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stocks.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/source_items.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stock_source_links.php
     * @magentoDataFixture Magento_InventoryIndexer::Test/_files/reindex_inventory.php
     * @magentoDbIsolation disabled
     */
    public function testExecuteWithDisabledProducts(): void
    {
        $sku = 'SKU-1';
        $expectedSalableData = [
            [
                'stock_name' => 'Default Stock',
                'qty' => 5.5,
                'manage_stock' => true,
                'stock_id' => 1
            ],
            [
                'stock_name' => 'EU-stock',
                'qty' => 8.5,
                'manage_stock' => true,
                'stock_id' => 10
            ],
            [
                'stock_name' => 'Global-stock',
                'qty' => 8.5,
                'manage_stock' => true,
                'stock_id' => 30
            ]
        ];

        $salableData = $this->getSalableQuantityDataBySku->execute($sku);
        $this->assertEquals($expectedSalableData, $salableData);
    }

    /**
     * @magentoDataFixture Magento_InventoryApi::Test/_files/products_with_amp_sku.php
     * @magentoDataFixture Magento_InventoryIndexer::Test/_files/reindex_inventory.php
     * @magentoDbIsolation disabled
     */
    public function testExecuteWithAmpSkuProducts(): void
    {
        $sku = 'Test &Sku';
        $expectedSalableData = [
            [
                'stock_name' => 'Default Stock',
                'qty' => 10,
                'manage_stock' => true,
                'stock_id' => 1
            ]
        ];

        $salableData = $this->getSalableQuantityDataBySku->execute($sku);
        $this->assertEquals($expectedSalableData, $salableData);
    }

    /**
     * @magentoDataFixture Magento_InventoryApi::Test/_files/products_with_special_char_sku.php
     * @magentoDataFixture Magento_InventoryIndexer::Test/_files/reindex_inventory.php
     * @magentoDbIsolation disabled
     */
    public function testExecuteWithSpecialCharSkuProduct(): void
    {
        $sku = 'Test-Sku-&`!@$#%^*+="';
        $expectedSalableData = [
            [
                'stock_name' => 'Default Stock',
                'qty' => 10,
                'manage_stock' => true,
                'stock_id' => 1
            ]
        ];

        $salableData = $this->getSalableQuantityDataBySku->execute($sku);
        $this->assertEquals($expectedSalableData, $salableData);
    }

    /**
     * @magentoConfigFixture current_store cataloginventory/item_options/manage_stock 0
     * @magentoDataFixture Magento_InventoryApi::Test/_files/products.php
     * @magentoDataFixture Magento_InventoryCatalog::Test/_files/source_items_on_default_source.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/sources.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stocks.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/source_items.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stock_source_links.php
     * @magentoDataFixture Magento_InventoryIndexer::Test/_files/reindex_inventory.php
     * @magentoDbIsolation disabled
     */
    public function testExecuteWithDisabledManageStock(): void
    {
        $sku = 'SKU-2';
        $expectedSalableData = [
            [
                'stock_name' => 'Default Stock',
                'qty' => null,
                'manage_stock' => false,
                'stock_id' => 1,
            ],
            [
                'stock_name' => 'US-stock',
                'qty' => null,
                'manage_stock' => false,
                'stock_id' => 20,
            ],
            [
                'stock_name' => 'Global-stock',
                'qty' => null,
                'manage_stock' => false,
                'stock_id' => 30,
            ],
        ];

        $salableData = $this->getSalableQuantityDataBySku->execute($sku);
        self::assertEquals($expectedSalableData, $salableData);
    }
}
