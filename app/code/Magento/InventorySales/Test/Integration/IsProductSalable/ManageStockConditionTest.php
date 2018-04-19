<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Test\Integration\IsProductSalable;

use Magento\InventorySales\Model\SalesChannelByWebsiteCodeProvider;
use Magento\InventorySalesApi\Api\IsProductSalableInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class ManageStockConditionTest extends TestCase
{
    /**
     * @var IsProductSalableInterface
     */
    private $isProductSalable;

    /**
     * @var SalesChannelByWebsiteCodeProvider
     */
    private $salesChannelByWebsiteCodeProvider;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->isProductSalable = Bootstrap::getObjectManager()->get(IsProductSalableInterface::class);
        $this->salesChannelByWebsiteCodeProvider
            = Bootstrap::getObjectManager()->get(SalesChannelByWebsiteCodeProvider::class);
    }

    /**
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/products.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/sources.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stocks.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/source_items.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stock_source_links.php
     * @magentoDataFixture ../../../../app/code/Magento/InventorySalesApi/Test/_files/stock_website_sales_channels.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryIndexer/Test/_files/reindex_inventory.php
     * @magentoConfigFixture default_store cataloginventory/item_options/manage_stock 0
     *
     * @param string $sku
     * @param string $websiteCode
     * @param bool $expectedResult
     * @return void
     *
     * @dataProvider executeWithManageStockFalseDataProvider
     */
    public function testExecuteWithManageStockFalse(string $sku, string $websiteCode, bool $expectedResult)
    {
        $salesChannel = $this->salesChannelByWebsiteCodeProvider->execute($websiteCode);
        $isSalable = $this->isProductSalable->execute($sku, $salesChannel);
        self::assertEquals($expectedResult, $isSalable);
    }

    /**
     * @return array
     */
    public function executeWithManageStockFalseDataProvider(): array
    {
        return [
            ['SKU-1', 'eu_website', true],
            ['SKU-1', 'us_website', false],
            ['SKU-1', 'global_website', true],
            ['SKU-2', 'eu_website', false],
            ['SKU-2', 'us_website', true],
            ['SKU-2', 'global_website', true],
            ['SKU-3', 'eu_website', true],
            ['SKU-3', 'us_website', false],
            ['SKU-3', 'global_website', true],
        ];
    }
}
