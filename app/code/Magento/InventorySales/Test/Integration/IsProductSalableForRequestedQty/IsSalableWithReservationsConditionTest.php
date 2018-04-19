<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Test\Integration\IsProductSalableForRequestedQty;

use Magento\InventoryConfigurationApi\Api\Data\StockItemConfigurationInterface;
use Magento\InventoryConfigurationApi\Api\GetStockItemConfigurationInterface;
use Magento\InventoryConfigurationApi\Api\SaveStockItemConfigurationInterface;
use Magento\InventoryReservations\Model\CleanupReservationsInterface;
use Magento\InventoryReservations\Model\ReservationBuilderInterface;
use Magento\InventoryReservationsApi\Api\AppendReservationsInterface;
use Magento\InventorySales\Model\SalesChannelByWebsiteCodeProvider;
use Magento\InventorySalesApi\Api\IsProductSalableForRequestedQtyInterface;
use Magento\InventorySalesApi\Api\StockResolverInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class IsSalableWithReservationsConditionTest extends TestCase
{
    /**
     * @var ReservationBuilderInterface
     */
    private $reservationBuilder;

    /**
     * @var AppendReservationsInterface
     */
    private $appendReservations;

    /**
     * @var CleanupReservationsInterface
     */
    private $cleanupReservations;

    /**
     * @var IsProductSalableForRequestedQtyInterface
     */
    private $isProductSalableForRequestedQty;

    /**
     * @var GetStockItemConfigurationInterface
     */
    private $getStockItemConfiguration;

    /**
     * @var SaveStockItemConfigurationInterface
     */
    private $saveStockItemConfiguration;

    /**
     * @var SalesChannelByWebsiteCodeProvider
     */
    private $salesChannelByWebsiteCodeProvider;

    /**
     * @var StockResolverInterface
     */
    private $stockResolver;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->reservationBuilder = Bootstrap::getObjectManager()->get(ReservationBuilderInterface::class);
        $this->appendReservations = Bootstrap::getObjectManager()->get(AppendReservationsInterface::class);
        $this->cleanupReservations = Bootstrap::getObjectManager()->get(CleanupReservationsInterface::class);
        $this->isProductSalableForRequestedQty
            = Bootstrap::getObjectManager()->get(IsProductSalableForRequestedQtyInterface::class);
        $this->getStockItemConfiguration = Bootstrap::getObjectManager()->get(
            GetStockItemConfigurationInterface::class
        );
        $this->saveStockItemConfiguration = Bootstrap::getObjectManager()->get(
            SaveStockItemConfigurationInterface::class
        );
        $this->salesChannelByWebsiteCodeProvider
            = Bootstrap::getObjectManager()->get(SalesChannelByWebsiteCodeProvider::class);
        $this->stockResolver
            = Bootstrap::getObjectManager()->get(StockResolverInterface::class);
    }

    /**
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/products.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/sources.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stocks.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stock_source_links.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/source_items.php
     * @magentoDataFixture ../../../../app/code/Magento/InventorySalesApi/Test/_files/stock_website_sales_channels.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryIndexer/Test/_files/reindex_inventory.php
     *
     * @param string $sku
     * @param string $websiteCode
     * @param bool $isSalable
     *
     * @dataProvider productIsSalableDataProvider
     */
    public function testProductIsSalable(string $sku, string $websiteCode, float $qty, bool $isSalable)
    {
        $salesChannel = $this->salesChannelByWebsiteCodeProvider->execute($websiteCode);
        self::assertEquals(
            $isSalable,
            $this->isProductSalableForRequestedQty->execute($sku, $salesChannel, $qty)->isSalable()
        );
    }

    /**
     * @return array
     */
    public function productIsSalableDataProvider(): array
    {
        return [
            ['SKU-1', 'eu_website', 1, true],
            ['SKU-1', 'us_website', 1, false],
            ['SKU-1', 'global_website', 1, true],
            ['SKU-2', 'eu_website', 1, false],
            ['SKU-2', 'us_website', 1, true],
            ['SKU-2', 'global_website', 1, true],
            ['SKU-3', 'eu_website', 1, false],
            ['SKU-3', 'us_website', 1, false],
            ['SKU-3', 'global_website', 1, false],
        ];
    }

    /**
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/products.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/sources.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stocks.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stock_source_links.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/source_items.php
     * @magentoDataFixture ../../../../app/code/Magento/InventorySalesApi/Test/_files/stock_website_sales_channels.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryIndexer/Test/_files/reindex_inventory.php
     *
     * @magentoConfigFixture default_store cataloginventory/item_options/min_qty 5
     *
     * @param string $sku
     * @param string $websiteCode
     * @param bool $isSalable
     *
     * @dataProvider productIsSalableWithUseConfigMinQtyDataProvider
     */
    public function testProductIsSalableWithUseConfigMinQty(
        string $sku,
        string $websiteCode,
        float $qty,
        bool $isSalable
    ) {
        $salesChannel = $this->salesChannelByWebsiteCodeProvider->execute($websiteCode);
        $stockId = (int)$this->stockResolver->get($salesChannel->getType(), $salesChannel->getCode())->getStockId();
        /** @var StockItemConfigurationInterface $stockItemConfiguration */
        $stockItemConfiguration = $this->getStockItemConfiguration->execute($sku, $stockId);
        $stockItemConfiguration->setUseConfigMinQty(true);
        $this->saveStockItemConfiguration->execute($sku, $stockId, $stockItemConfiguration);

        self::assertEquals(
            $isSalable,
            $this->isProductSalableForRequestedQty->execute($sku, $salesChannel, $qty)->isSalable()
        );
    }

    /**
     * @return array
     */
    public function productIsSalableWithUseConfigMinQtyDataProvider(): array
    {
        return [
            ['SKU-1', 'eu_website', 3, true],
            ['SKU-1', 'eu_website', 4, false],
            ['SKU-1', 'global_website', 3, true],
            ['SKU-1', 'global_website', 4, false],
            ['SKU-2', 'us_website', 1, false],
            ['SKU-2', 'global_website', 1, false],
            ['SKU-3', 'eu_website', 1, false],
            ['SKU-3', 'global_website', 1, false],
        ];
    }

    /**
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/products.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/sources.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stocks.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stock_source_links.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/source_items.php
     * @magentoDataFixture ../../../../app/code/Magento/InventorySalesApi/Test/_files/stock_website_sales_channels.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryIndexer/Test/_files/reindex_inventory.php
     *
     * @param string $sku
     * @param string $websiteCode
     * @param bool $isSalable
     *
     * @dataProvider productIsSalableWithMinQtyDataProvider
     */
    public function testProductIsSalableWithMinQty(string $sku, string $websiteCode, float $qty, bool $isSalable)
    {
        $salesChannel = $this->salesChannelByWebsiteCodeProvider->execute($websiteCode);
        $stockId = (int)$this->stockResolver->get($salesChannel->getType(), $salesChannel->getCode())->getStockId();

        /** @var StockItemConfigurationInterface $stockItemConfiguration */
        $stockItemConfiguration = $this->getStockItemConfiguration->execute($sku, $stockId);
        $stockItemConfiguration->setUseConfigMinQty(false);
        $stockItemConfiguration->setMinQty(5);
        $this->saveStockItemConfiguration->execute($sku, $stockId, $stockItemConfiguration);

        self::assertEquals(
            $isSalable,
            $this->isProductSalableForRequestedQty->execute($sku, $salesChannel, $qty)->isSalable()
        );
    }

    /**
     * @return array
     */
    public function productIsSalableWithMinQtyDataProvider(): array
    {
        return [
            ['SKU-1', 'eu_website', 3, true],
            ['SKU-1', 'eu_website', 4, false],
            ['SKU-1', 'global_website', 3, true],
            ['SKU-1', 'global_website', 4, false],
            ['SKU-2', 'us_website', 1, false],
            ['SKU-2', 'global_website', 1, false],
            ['SKU-3', 'eu_website', 1, false],
            ['SKU-3', 'global_website', 1, false],
        ];
    }

    /**
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/products.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/sources.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stocks.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stock_source_links.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/source_items.php
     * @magentoDataFixture ../../../../app/code/Magento/InventorySalesApi/Test/_files/stock_website_sales_channels.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryIndexer/Test/_files/reindex_inventory.php
     */
    public function testProductIsOutOfStockIfReservationsArePresent()
    {
        // emulate order placement (reserve -8.5 units)
        $this->appendReservations->execute([
            $this->reservationBuilder->setStockId(10)->setSku('SKU-1')->setQuantity(-8.5)->build(),
        ]);
        $salesChannel = $this->salesChannelByWebsiteCodeProvider->execute('eu_website');
        self::assertFalse($this->isProductSalableForRequestedQty->execute('SKU-1', $salesChannel, 1)->isSalable());

        $this->appendReservations->execute([
            // unreserve 8.5 units for cleanup
            $this->reservationBuilder->setStockId(10)->setSku('SKU-1')->setQuantity(8.5)->build(),
        ]);
        $this->cleanupReservations->execute();
    }
}
