<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySalesAdminUi\Test\Integration\Model;

use Magento\Catalog\Test\Fixture\Product as ProductFixture;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryApi\Test\Fixture\Source as SourceFixture;
use Magento\InventoryApi\Test\Fixture\SourceItems as SourceItemsFixture;
use Magento\InventoryApi\Test\Fixture\Stock as StockFixture;
use Magento\InventoryApi\Test\Fixture\StockSourceLinks as StockSourceLinksFixture;
use Magento\InventoryConfigurationApi\Exception\SkuIsNotAssignedToStockException;
use Magento\InventorySalesAdminUi\Model\GetSalableQuantityDataBySku;
use Magento\InventorySalesApi\Test\Fixture\StockSalesChannels as StockSalesChannelsFixture;
use Magento\TestFramework\Fixture\AppIsolation;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorage;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Fixture\DbIsolation;
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
     * @var DataFixtureStorage
     */
    private $fixtures;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->fixtures = DataFixtureStorageManager::getStorage();
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

    /**
     * For non-default sources, the salable quantity should permit up to an 8-digit value, just as the default source.
     *
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws SkuIsNotAssignedToStockException
     * @return void
     */
    #[
        DbIsolation(false),
        AppIsolation(true),
        DataFixture(SourceFixture::class, as: 'source2'),
        DataFixture(StockFixture::class, as: 'stock2'),
        DataFixture(
            StockSourceLinksFixture::class,
            [
                ['stock_id' => '$stock2.stock_id$', 'source_code' => '$source2.source_code$'],
            ]
        ),
        DataFixture(
            StockSalesChannelsFixture::class,
            ['stock_id' => '$stock2.stock_id$', 'sales_channels' => ['base']]
        ),

        DataFixture(ProductFixture::class, ['sku' => 'simple1'], 'p1'),
        DataFixture(
            SourceItemsFixture::class,
            [
                ['sku' => '$p1.sku$', 'source_code' => 'default', 'quantity' => 12345678],
                ['sku' => '$p1.sku$', 'source_code' => '$source2.source_code$', 'quantity' => 12345678],
            ]
        ),
    ]
    public function testSalableQuantityForMaxAllowedDigits(): void
    {
        $product = $this->fixtures->get('p1');
        $salableData = $this->getSalableQuantityDataBySku->execute($product->getSku());
        foreach ($salableData as $data) {
            $this->assertEquals(12345678, (int)$data['qty']);
        }
    }
}
