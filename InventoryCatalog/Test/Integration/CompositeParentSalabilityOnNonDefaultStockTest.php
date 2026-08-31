<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Test\Integration;

use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterfaceFactory;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;
use Magento\InventorySalesApi\Model\GetStockItemDataInterface;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DbIsolation;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Regression guard for magento/inventory#3350.
 *
 * A composite parent whose children are out of stock at the DEFAULT source but still in stock at another
 * source must remain salable in the stock those other sources back. The parent's legacy stock item is
 * default-stock data; recomputing it from default-scope children and then letting it decide another
 * stock's salability is what #3350 reported.
 */
class CompositeParentSalabilityOnNonDefaultStockTest extends TestCase
{
    private const NON_DEFAULT_STOCK_ID = 10;

    private const PARENT_SKU = 'configurable_1';

    private const CHILD_SKUS = ['simple_11', 'simple_21', 'simple_31'];

    /**
     * @var GetStockItemDataInterface
     */
    private $getStockItemData;

    protected function setUp(): void
    {
        $this->getStockItemData = Bootstrap::getObjectManager()->get(GetStockItemDataInterface::class);
    }

    /**
     * @return void
     */
    #[
        DbIsolation(false),
        DataFixture('Magento_InventoryApi::Test/_files/sources.php'),
        DataFixture('Magento_InventoryApi::Test/_files/stocks.php'),
        DataFixture('Magento_InventoryApi::Test/_files/stock_source_links.php'),
        DataFixture('Magento_InventorySalesApi::Test/_files/websites_with_stores.php'),
        DataFixture('Magento_InventorySalesApi::Test/_files/stock_website_sales_channels.php'),
        DataFixture('Magento/ConfigurableProduct/_files/configurable_attribute.php'),
        DataFixture('Magento_InventoryConfigurableProductIndexer::Test/_files/product_configurable_multiple.php'),
        DataFixture('Magento_InventoryConfigurableProductIndexer::Test/_files/source_items_configurable_multiple.php'),
    ]
    public function testTheParentStaysSalableWhenOnlyTheDefaultSourceRunsOut(): void
    {
        $before = $this->salabilityInNonDefaultStock();
        self::assertSame(
            1,
            $before,
            'PREMISE FAILED: the parent must be salable in the non-default stock to begin with, otherwise '
            . 'this test cannot detect the regression it guards against.'
        );

        // The default source runs out. The eu sources backing stock 10 are untouched.
        $this->setDefaultSourceOutOfStock();

        self::assertSame(
            1,
            $this->salabilityInNonDefaultStock(),
            'magento/inventory#3350 has been reintroduced: the parent lost its salability in a stock backed '
            . 'by sources that still hold stock, because its default-stock legacy row was recomputed from '
            . 'default-scope children and then vetoed the other stock.'
        );
    }

    /**
     * @return int
     */
    private function salabilityInNonDefaultStock(): int
    {
        $data = $this->getStockItemData->execute(self::PARENT_SKU, self::NON_DEFAULT_STOCK_ID);
        self::assertNotNull(
            $data,
            sprintf('No index row for "%s" in stock %d.', self::PARENT_SKU, self::NON_DEFAULT_STOCK_ID)
        );

        return (int)$data[GetStockItemDataInterface::IS_SALABLE];
    }

    /**
     * Put every child out of stock on the default source only.
     *
     * @return void
     */
    private function setDefaultSourceOutOfStock(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $sourceItemFactory = $objectManager->get(SourceItemInterfaceFactory::class);

        $sourceItems = [];
        foreach (self::CHILD_SKUS as $sku) {
            $sourceItem = $sourceItemFactory->create();
            $sourceItem->setSourceCode('default');
            $sourceItem->setSku($sku);
            $sourceItem->setQuantity(0);
            $sourceItem->setStatus(SourceItemInterface::STATUS_OUT_OF_STOCK);
            $sourceItems[] = $sourceItem;
        }

        $objectManager->get(SourceItemsSaveInterface::class)->execute($sourceItems);
    }
}
