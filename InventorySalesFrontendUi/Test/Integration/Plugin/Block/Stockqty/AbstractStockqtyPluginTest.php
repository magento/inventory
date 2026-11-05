<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventorySalesFrontendUi\Test\Integration\Plugin\Block\Stockqty;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Test\Fixture\Product as ProductFixture;
use Magento\CatalogInventory\Block\Stockqty\DefaultStockqty;
use Magento\Framework\Registry;
use Magento\Framework\View\LayoutInterface;
use Magento\InventoryApi\Test\Fixture\Source as SourceFixture;
use Magento\InventoryApi\Test\Fixture\Stock as StockFixture;
use Magento\InventoryApi\Test\Fixture\StockSourceLinks as StockSourceLinksFixture;
use Magento\InventorySalesApi\Test\Fixture\StockSalesChannels as StockSalesChannelsFixture;
use Magento\TestFramework\Fixture\AppArea;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Fixture\DbIsolation;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AbstractStockqtyPluginTest extends TestCase
{
    /**
     * @var Registry
     */
    private $registry;

    protected function setUp(): void
    {
        $this->registry = Bootstrap::getObjectManager()->get(Registry::class);
    }

    protected function tearDown(): void
    {
        $this->registry->unregister('current_product');
    }

    #[
        AppArea('frontend'),
        DbIsolation(false),
        DataFixture(SourceFixture::class, as: 'source'),
        DataFixture(StockFixture::class, as: 'stock'),
        DataFixture(
            StockSourceLinksFixture::class,
            [['stock_id' => '$stock.stock_id$', 'source_code' => '$source.source_code$']]
        ),
        DataFixture(
            StockSalesChannelsFixture::class,
            ['stock_id' => '$stock.stock_id$', 'sales_channels' => ['base']]
        ),
        DataFixture(ProductFixture::class, as: 'product'),
    ]
    public function testMessageIsHiddenForSkuNotAssignedToWebsiteStock(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $sku = $objectManager->get(DataFixtureStorageManager::class)->getStorage()->get('product')->getSku();
        $product = $objectManager->get(ProductRepositoryInterface::class)->get($sku, false, null, true);
        $this->registry->register('current_product', $product);
        $block = $objectManager->get(LayoutInterface::class)->createBlock(DefaultStockqty::class);

        $this->assertFalse($block->isMsgVisible());
    }
}
