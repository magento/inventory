<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Test\Integration\SourceSelection;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\SourceItemRepositoryInterface;
use Magento\InventoryInStorePickup\Model\SourceSelection\GetSourceItemQtyAvailableService;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class GetSourceItemQtyAvailableServiceTest extends TestCase
{
    /**
     * @var GetSourceItemQtyAvailableService
     */
    private $getSourceItemQtyAvailableService;

    /**
     * @var SourceItemRepositoryInterface
     */
    private $sourceItemRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    protected function setUp()
    {
        $om = Bootstrap::getObjectManager();
        $this->getSourceItemQtyAvailableService = $om->get(GetSourceItemQtyAvailableService::class);
        $this->sourceItemRepository = $om->get(SourceItemRepositoryInterface::class);
        $this->searchCriteriaBuilder = $om->get(SearchCriteriaBuilder::class);
    }

    /**
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/sources.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stocks.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stock_source_links.php
     * @magentoDataFixture ../../../../app/code/Magento/InventorySalesApi/Test/_files/websites_with_stores.php
     * @magentoDataFixture ../../../../app/code/Magento/InventorySalesApi/Test/_files/stock_website_sales_channels.php
     * @magentoDataFixture Magento/Checkout/_files/simple_product.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryShipping/Test/_files/source_items_for_simple_on_multi_source.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryIndexer/Test/_files/reindex_inventory.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryShipping/Test/_files/create_quote_on_eu_website.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryShipping/Test/_files/order_simple_product.php
     *
     * @magentoDbIsolation disabled
     * @throws
     */
    public function testWithoutStorePickup()
    {
        foreach ($this->getSourceItems('eu-1,eu-2,eu-3', 'simple') as $sourceItem) {
            $this->assertEquals(
                $sourceItem->getQuantity(),
                $this->getSourceItemQtyAvailableService->execute($sourceItem)
            );
        }
    }

    /**
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/products.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/sources.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stocks.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stock_source_links.php
     * @magentoDataFixture ../../../../app/code/Magento/InventorySalesApi/Test/_files/websites_with_stores.php
     * @magentoDataFixture ../../../../app/code/Magento/InventorySalesApi/Test/_files/stock_website_sales_channels.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryInStorePickup/Test/_files/source_items.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryIndexer/Test/_files/reindex_inventory.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryInStorePickup/Test/_files/source_pickup_location_attributes.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryInStorePickup/Test/_files/create_in_store_pickup_quote_on_eu_website.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryInStorePickup/Test/_files/add_products_from_eu_stock_to_cart.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryInStorePickup/Test/_files/place_order.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryInStorePickup/Test/_files/set_order_pickup_location.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryInStorePickup/Test/_files/create_multiple_quotes_on_eu_website.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryInStorePickup/Test/_files/place_multiple_orders_on_eu_website.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryInStorePickup/Test/_files/mark_sources_as_pickup_location.php
     *
     * @magentoConfigFixture store_for_eu_website_store carriers/in_store/active 1
     * @magentoConfigFixture store_for_eu_website_store carriers/flatrate/active 1
     *
     * @magentoDbIsolation disabled
     *
     * @dataProvider singleStorePickupOrderProvider
     *
     * @param string $sourceCode
     * @param string $sku
     * @param float $qtyExpected
     *
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function testSingleStorePickupOrder(string $sourceCode, string $sku, float $qtyExpected)
    {
        $sourceItem = $this->getSourceItem($sourceCode, $sku);
        $this->assertEquals($qtyExpected, $this->getSourceItemQtyAvailableService->execute($sourceItem));
    }

    /**
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/products.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/sources.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stocks.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stock_source_links.php
     * @magentoDataFixture ../../../../app/code/Magento/InventorySalesApi/Test/_files/websites_with_stores.php
     * @magentoDataFixture ../../../../app/code/Magento/InventorySalesApi/Test/_files/stock_website_sales_channels.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryInStorePickup/Test/_files/source_items.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryIndexer/Test/_files/reindex_inventory.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryInStorePickup/Test/_files/source_pickup_location_attributes.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryInStorePickup/Test/_files/create_in_store_pickup_quote_on_eu_website.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryInStorePickup/Test/_files/add_products_from_eu_stock_to_cart.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryInStorePickup/Test/_files/place_order.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryInStorePickup/Test/_files/set_order_pickup_location.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryInStorePickup/Test/_files/create_multiple_quotes_on_eu_website.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryInStorePickup/Test/_files/place_multiple_orders_on_eu_website.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryInStorePickup/Test/_files/set_orders_pickup_location.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryInStorePickup/Test/_files/mark_sources_as_pickup_location.php
     *
     * @magentoConfigFixture store_for_eu_website_store carriers/in_store/active 1
     * @magentoConfigFixture store_for_eu_website_store carriers/flatrate/active 1
     *
     * @magentoDbIsolation disabled
     *
     * @dataProvider multipleStorePickupOrdersProvider
     *
     * @param string $sourceCode
     * @param string $sku
     * @param float $qtyExpected
     *
     * @throws
     */
    public function testMultipleStorePickupOrders(string $sourceCode, string $sku, float $qtyExpected)
    {
        $sourceItem = $this->getSourceItem($sourceCode, $sku);
        $this->assertEquals($qtyExpected, $this->getSourceItemQtyAvailableService->execute($sourceItem));
    }

    /**
     * @param string $sourceCodes
     * @param string $sku
     *
     * @return SourceItemInterface[]
     */
    private function getSourceItems(string $sourceCodes, string $sku): array
    {
        return $this->sourceItemRepository->getList(
            $this->searchCriteriaBuilder
                ->addFilter(
                    SourceItemInterface::SKU,
                    $sku
                )->addFilter(
                    SourceItemInterface::SOURCE_CODE,
                    $sourceCodes,
                    'in'
                )->create()
        )->getItems();
    }

    /**
     * @return array
     */
    public function singleStorePickupOrderProvider(): array
    {
        return [
            ['eu-1', 'SKU-1', 2.0], //3.5 reserved
            ['eu-2', 'SKU-1', 3.5],
            ['eu-3', 'SKU-1', 10.0],
            ['us-1', 'SKU-2', 5.0],
            ['eu-2', 'SKU-3', 6.0],
            ['eu-2', 'SKU-4', 6.0],
            ['eu-1', 'SKU-6', 666.0],
        ];
    }

    /**
     * @return array
     */
    public function multipleStorePickupOrdersProvider(): array
    {
        return [
            ['eu-1', 'SKU-1', 1.0], //3.5 + 1.0 reserved
            ['eu-2', 'SKU-1', 3.5],
            ['eu-2', 'SKU-2', 2.0], // 1.0 reserved
            ['eu-3', 'SKU-1', 10.0],
            ['us-1', 'SKU-2', 5.0],
            ['eu-2', 'SKU-3', 6.0],
            ['eu-2', 'SKU-4', 6.0],
            ['eu-1', 'SKU-6', 665.0], // 1.0 reserved
        ];
    }

    /**
     * @param string $sourceCode
     * @param $sku
     *
     * @return SourceItemInterface
     */
    private function getSourceItem(string $sourceCode, $sku): SourceItemInterface
    {
        return current(
            $this->sourceItemRepository->getList(
                $this->searchCriteriaBuilder
                    ->addFilter(
                        SourceItemInterface::SKU,
                        $sku
                    )->addFilter(
                        SourceItemInterface::SOURCE_CODE,
                        $sourceCode
                    )->create()
            )->getItems()
        );
    }
}
