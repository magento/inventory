<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Test\Integration\Extension;

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

    protected function setUp()
    {
        $this->objectManager = Bootstrap::getObjectManager();

        $this->orderRepository = $this->objectManager->get(OrderRepositoryInterface::class);
        $this->searchCriteriaBuilder = $this->objectManager->get(SearchCriteriaBuilder::class);
        $this->orderExtensionFactory = $this->objectManager->get(OrderExtensionFactory::class);
    }

    /**
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/products.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/sources.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryInStorePickup/Test/_files/source_pickup_location_attributes.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stocks.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stock_source_links.php
     * @magentoDataFixture ../../../../app/code/Magento/InventorySalesApi/Test/_files/websites_with_stores.php
     * @magentoDataFixture ../../../../app/code/Magento/InventorySalesApi/Test/_files/stock_website_sales_channels.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/source_items_eu_stock_only.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryIndexer/Test/_files/reindex_inventory.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryInStorePickup/Test/_files/create_in_store_pickup_quote_on_eu_website.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryInStorePickup/Test/_files/add_products_from_eu_stock_to_cart.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryInStorePickup/Test/_files/place_order.php
     *
     * @magentoConfigFixture store_for_eu_website_store carriers/in_store/active 1
     * @magentoConfigFixture store_for_eu_website_store carriers/in_store/price 5.95
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
        $orderId = $createdOrder->getEntityId();

        $extension = $createdOrder->getExtensionAttributes();

        if (empty($extension)) {
            /** @var \Magento\Sales\Api\Data\OrderExtensionInterface $extension */
            $extension = $this->orderExtensionFactory->create();
        }

        $extension->setPickupLocationCode($sourceId);
        $createdOrder->setExtensionAttributes($extension);

        $this->orderRepository->save($createdOrder);

        // Remove value to re-load from DB during 'get'.
        $extension->setPickupLocationCode(null);

        $order = $this->orderRepository->get($orderId);

        $this->assertEquals($order->getExtensionAttributes()->getPickupLocationCode(), $sourceId);
    }
}
