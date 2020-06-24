<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupSales\Test\Integration\Extension;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\ObjectManagerInterface;
use Magento\Sales\Api\Data\OrderExtensionFactory;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class OrderExtensionTest extends TestCase
{
    /** @var ObjectManagerInterface */
    private $objectManager;

    /** @var OrderRepositoryInterface */
    private $orderRepository;

    /** @var SearchCriteriaBuilder */
    private $searchCriteriaBuilder;

    /** @var  OrderExtensionFactory */
    private $orderExtensionFactory;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();

        $this->orderRepository = $this->objectManager->get(OrderRepositoryInterface::class);
        $this->searchCriteriaBuilder = $this->objectManager->get(SearchCriteriaBuilder::class);
        $this->orderExtensionFactory = $this->objectManager->get(OrderExtensionFactory::class);
    }

    /**
     * @magentoDataFixture Magento_InventoryApi::Test/_files/products.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/sources.php
     * @magentoDataFixture Magento_InventoryInStorePickupApi::Test/_files/source_addresses.php
     * @magentoDataFixture Magento_InventoryInStorePickupApi::Test/_files/source_pickup_location_attributes.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stocks.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stock_source_links.php
     * @magentoDataFixture Magento_InventorySalesApi::Test/_files/websites_with_stores.php
     * @magentoDataFixture Magento_InventorySalesApi::Test/_files/stock_website_sales_channels.php
     * @magentoDataFixture Magento_InventoryInStorePickupApi::Test/_files/source_items_eu_stock_only.php
     * @magentoDataFixture Magento_InventoryIndexer::Test/_files/reindex_inventory.php
     * @magentoDataFixture Magento_InventoryInStorePickupSalesApi::Test/_files/create_in_store_pickup_quote_on_eu_website_guest.php
     * @magentoDataFixture Magento_InventoryInStorePickupSalesApi::Test/_files/place_order.php
     *
     * @magentoConfigFixture store_for_eu_website_store carriers/instore/active 1
     * @magentoConfigFixture store_for_eu_website_store carriers/instore/price 5.95
     *
     * @magentoDbIsolation disabled
     */
    public function testPickupLocationSaveWithOrder()
    {
        $sourceId = 'eu-1';

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('increment_id', 'in_store_pickup_test_order')
            ->create();
        /** @var OrderInterface $createdOrder */
        $createdOrder = current($this->orderRepository->getList($searchCriteria)->getItems());

        $this->assertEquals($createdOrder->getExtensionAttributes()->getPickupLocationCode(), $sourceId);
    }
}
