<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupShipping\Test\Integration\Carrier;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\InventoryInStorePickupShippingApi\Model\Carrier\InStorePickup;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Api\Data\ShippingAssignmentInterfaceFactory;
use Magento\Quote\Api\Data\ShippingInterfaceFactory;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class InStorePickupTest extends TestCase
{
    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * @var InStorePickup
     */
    private $inStorePickup;

    /**
     * @inheritdoc
     */
    public function setUp(): void
    {
        $this->searchCriteriaBuilder = Bootstrap::getObjectManager()->get(SearchCriteriaBuilder::class);
        $this->cartRepository = Bootstrap::getObjectManager()->get(CartRepositoryInterface::class);
        $this->inStorePickup = Bootstrap::getObjectManager()->get(InStorePickup::class);
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
     *
     * @magentoConfigFixture store_for_eu_website_store carriers/instore/active 1
     * @magentoConfigFixture store_for_eu_website_store carriers/instore/price 5.95
     *
     * @magentoDbIsolation disabled
     */
    public function testShippingCost()
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('reserved_order_id', 'in_store_pickup_test_order')
            ->create();
        /** @var \Magento\Quote\Api\Data\CartInterface $cart */
        $cart = current($this->cartRepository->getList($searchCriteria)->getItems());

        $this->assertEquals("instore_pickup", $cart->getShippingAddress()->getShippingMethod());
        $this->assertEquals(5.95, $cart->getShippingAddress()->getShippingAmount());
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
     * @magentoDataFixture Magento_InventoryApi::Test/_files/source_items_eu_stock_only.php
     * @magentoDataFixture Magento_InventoryIndexer::Test/_files/reindex_inventory.php
     * @magentoDataFixture Magento_InventoryInStorePickupSalesApi::Test/_files/create_in_store_pickup_quote_on_eu_website_guest.php
     *
     * @magentoConfigFixture store_for_eu_website_store carriers/instore/active 1
     * @magentoConfigFixture store_for_eu_website_store carriers/instore/price 5.95
     *
     * @magentoAppArea frontend
     *
     * @magentoDbIsolation disabled
     */
    public function testShippingMethodWithoutPickupLocations()
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('reserved_order_id', 'in_store_pickup_test_order')
            ->create();
        /** @var \Magento\Quote\Model\Quote $cart */
        $cart = current($this->cartRepository->getList($searchCriteria)->getItems());

        /** @var ShippingAssignmentInterfaceFactory $shippingAssignmentFactory */
        $shippingAssignmentFactory = Bootstrap::getObjectManager()->get(ShippingAssignmentInterfaceFactory::class);
        /** @var ShippingAssignmentInterface $shippingAssignment */
        $shippingAssignment = $shippingAssignmentFactory->create();
        /** @var ShippingInterfaceFactory $shippingFactory */
        $shippingFactory = Bootstrap::getObjectManager()->get(ShippingInterfaceFactory::class);
        $shipping = $shippingFactory->create();
        $shipping->setMethod(InStorePickup::DELIVERY_METHOD);
        $shipping->setAddress($cart->getShippingAddress());
        $shippingAssignment->setShipping($shipping);
        $shippingAssignment->setItems($cart->getAllItems());
        $cart->getExtensionAttributes()->setShippingAssignments([$shippingAssignment]);

        $this->cartRepository->save($cart);

        $cart = $this->cartRepository->get($cart->getId());

        $this->assertEmpty($cart->getShippingAddress()->getShippingMethod());
        $this->assertEquals(0, $cart->getShippingAddress()->getShippingAmount());
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
     * @magentoDataFixture Magento_InventoryApi::Test/_files/source_items_eu_stock_only.php
     * @magentoDataFixture Magento_InventoryIndexer::Test/_files/reindex_inventory.php
     * @magentoDataFixture Magento_InventoryInStorePickupSalesApi::Test/_files/create_in_store_pickup_quote_on_eu_website_guest.php
     *
     * @magentoConfigFixture store_for_eu_website_store carriers/instore/active 1
     * @magentoConfigFixture store_for_eu_website_store carriers/instore/price 5.95
     *
     * @magentoDbIsolation disabled
     */
    public function testShippingMethodWithoutProductsIntersection()
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('reserved_order_id', 'in_store_pickup_test_order')
            ->create();
        /** @var \Magento\Quote\Api\Data\CartInterface $cart */
        $cart = current($this->cartRepository->getList($searchCriteria)->getItems());

        $this->assertEmpty($cart->getShippingAddress()->getShippingMethod());
        $this->assertEquals(0, $cart->getShippingAddress()->getShippingAmount());
    }

    /**
     * @return void
     */
    public function testShippingMethodCodeIsAvailable()
    {
        $this->assertArrayHasKey('pickup', $this->inStorePickup->getAllowedMethods());
    }
}
