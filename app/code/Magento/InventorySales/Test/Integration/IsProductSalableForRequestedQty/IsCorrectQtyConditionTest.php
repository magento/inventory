<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Test\Integration\IsProductSalableForRequestedQty;

use Magento\InventoryConfigurationApi\Api\GetStockItemConfigurationInterface;
use Magento\InventoryConfigurationApi\Api\SaveStockItemConfigurationInterface;
use Magento\InventorySales\Model\SalesChannelByWebsiteCodeProvider;
use Magento\InventorySalesApi\Api\IsProductSalableForRequestedQtyInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class IsCorrectQtyConditionTest extends TestCase
{
    /**
     * @var GetStockItemConfigurationInterface
     */
    private $getStockItemConfig;

    /**
     * @var SaveStockItemConfigurationInterface
     */
    private $saveStockItemConfig;

    /**
     * @var IsProductSalableForRequestedQtyInterface
     */
    private $isProductSalableForRequestedQty;

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

        $this->getStockItemConfig = Bootstrap::getObjectManager()->get(GetStockItemConfigurationInterface::class);
        $this->saveStockItemConfig = Bootstrap::getObjectManager()->get(SaveStockItemConfigurationInterface::class);
        $this->isProductSalableForRequestedQty
            = Bootstrap::getObjectManager()->get(IsProductSalableForRequestedQtyInterface::class);
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
     * @dataProvider executeWithMissingConfigurationDataProvider
     */
    public function testExecuteWithMissingConfiguration($sku, $websiteCode, $requestedQty, bool $expectedResult)
    {
        $salesChannel = $this->salesChannelByWebsiteCodeProvider->execute($websiteCode);
        $result = $this->isProductSalableForRequestedQty->execute($sku, $salesChannel, $requestedQty);
        $this->assertEquals($expectedResult, $result->isSalable());
    }

    public function executeWithMissingConfigurationDataProvider(): array
    {
        return [
            ['SKU-2', 'eu_webiste', 1, false],
        ];
    }
    
    public function testExecuteWithUseConfigMinSaleQty()
    {
        $this->markTestIncomplete('Still to implement');
    }

    public function testExecuteWithMinSaleQty()
    {
        $this->markTestIncomplete('Still to implement');
    }

    public function testExecuteWithUseConfigMaxSaleQty()
    {
        $this->markTestIncomplete('Still to implement');
    }

    public function testExecuteWithMaxSaleQty()
    {
        $this->markTestIncomplete('Still to implement');
    }

    public function testExecuteWithQtyIncrements()
    {
        $this->markTestIncomplete('Still to implement');
    }
}
