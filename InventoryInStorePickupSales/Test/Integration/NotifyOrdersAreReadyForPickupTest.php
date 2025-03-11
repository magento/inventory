<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupSales\Test\Integration;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\InventoryInStorePickupSalesApi\Api\NotifyOrdersAreReadyForPickupInterface;
use Magento\Sales\Api\Data\OrderExtensionFactory;
use Magento\Sales\Api\Data\OrderExtensionInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Api\Data\ShipmentItemInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Mail\Template\TransportBuilderMock;

/**
 * @inheritdoc
 */
class NotifyOrdersAreReadyForPickupTest extends \PHPUnit\Framework\TestCase
{
    /** @var ObjectManagerInterface */
    private $objectManager;

    /** @var OrderRepositoryInterface */
    private $orderRepository;

    /** @var ShipmentRepositoryInterface */
    private $shipmentRepository;

    /** @var SearchCriteriaBuilder */
    private $searchCriteriaBuilder;

    /** @var NotifyOrdersAreReadyForPickupInterface */
    private $notifyOrderIsReadyForPickup;

    /** @var OrderExtensionInterface */
    private $orderExtensionFactory;

    /** @var RequestInterface */
    private $request;

    /** @var TransportBuilderMock */
    private $transportBuilder;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();

        $this->orderRepository = $this->objectManager->get(OrderRepositoryInterface::class);
        $this->shipmentRepository = $this->objectManager->get(ShipmentRepositoryInterface::class);
        $this->searchCriteriaBuilder = $this->objectManager->get(SearchCriteriaBuilder::class);
        $this->notifyOrderIsReadyForPickup = $this->objectManager->get(NotifyOrdersAreReadyForPickupInterface::class);
        $this->orderExtensionFactory = $this->objectManager->get(OrderExtensionFactory::class);
        $this->request = $this->objectManager->get(RequestInterface::class);
        $this->transportBuilder = $this->objectManager->get(TransportBuilderMock::class);
    }

    /**
     * @magentoDataFixture Magento_InventoryApi::Test/_files/products.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/sources.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stocks.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stock_source_links.php
     * @magentoDataFixture Magento_InventorySalesApi::Test/_files/websites_with_stores.php
     * @magentoDataFixture Magento_InventorySalesApi::Test/_files/stock_website_sales_channels.php
     * @magentoDataFixture Magento_InventoryInStorePickupApi::Test/_files/source_items.php
     * @magentoDataFixture Magento_InventoryIndexer::Test/_files/reindex_inventory.php
     * @magentoDataFixture Magento_InventoryInStorePickupApi::Test/_files/source_addresses.php
     * @magentoDataFixture Magento_InventoryInStorePickupApi::Test/_files/source_pickup_location_attributes.php
     * @magentoDataFixture Magento_InventoryInStorePickupSalesApi::Test/_files/create_in_store_pickup_quote_on_eu_website_guest.php
     * @magentoDataFixture Magento_InventoryInStorePickupSalesApi::Test/_files/place_order.php
     *
     * @magentoConfigFixture store_for_eu_website_store carriers/instore/active 1
     *
     * @magentoDbIsolation disabled
     * @magentoAppIsolation enabled
     * @dataProvider dataProvider
     *
     * @param string $sourceId
     * @param string|null $errorMessage
     * @throws
     */
    public function testExecuteForNotReadyForPickupOrders(string $sourceId, ?string $errorMessage)
    {
        $createdOrder = $this->getCreatedOrder();
        $this->setPickupLocation($createdOrder, $sourceId);

        // @see \Magento\InventoryShipping\Plugin\Sales\Shipment\AssignSourceCodeToShipmentPlugin::afterCreate
        $this->request->setParams(['sourceCode' => $sourceId]);

        $orderId = (int)$createdOrder->getEntityId();
        $result = $this->notifyOrderIsReadyForPickup->execute([$orderId]);

        $this->assertFalse($result->isSuccessful());
        $this->assertEquals(current($result->getErrors())['message'], $errorMessage);
        if ($message = $this->transportBuilder->getSentMessage()) {
            $this->assertNotEquals(
                "Your store_view_eu_website order is ready for pickup",
                $message->getSubject()
            );
        }
    }

    /**
     * @magentoDataFixture Magento_InventoryApi::Test/_files/products.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/sources.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stocks.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stock_source_links.php
     * @magentoDataFixture Magento_InventorySalesApi::Test/_files/websites_with_stores.php
     * @magentoDataFixture Magento_InventorySalesApi::Test/_files/stock_website_sales_channels.php
     * @magentoDataFixture Magento_InventoryInStorePickupApi::Test/_files/source_items.php
     * @magentoDataFixture Magento_InventoryIndexer::Test/_files/reindex_inventory.php
     * @magentoDataFixture Magento_InventoryInStorePickupApi::Test/_files/source_addresses.php
     * @magentoDataFixture Magento_InventoryInStorePickupApi::Test/_files/source_pickup_location_attributes.php
     * @magentoDataFixture Magento_InventoryInStorePickupSalesApi::Test/_files/create_in_store_pickup_quote_on_eu_website_guest.php
     * @magentoDataFixture Magento_InventoryInStorePickupSalesApi::Test/_files/place_order.php
     *
     * @magentoConfigFixture store_for_eu_website_store carriers/instore/active 1
     *
     * @magentoDbIsolation disabled
     */
    public function testExecuteReadyForPickUpOrders()
    {
        $sourceId = 'eu-2';
        $createdOrder = $this->getCreatedOrder();
        $this->setPickupLocation($createdOrder, $sourceId);

        // @see \Magento\InventoryShipping\Plugin\Sales\Shipment\AssignSourceCodeToShipmentPlugin::afterCreate
        $this->request->setParams(['sourceCode' => $sourceId]);

        $orderId = (int)$createdOrder->getEntityId();
        $this->notifyOrderIsReadyForPickup->execute([$orderId]);

        /** @var ShipmentInterface $createdShipment */
        $createdShipment = $this->getCreatedShipment($orderId);
        /** @var ShipmentItemInterface $shipmentItem */
        $shipmentItem = current($createdShipment->getItems());

        // assert created shipment;
        $this->assertTrue((bool)$createdShipment);
        $this->assertEquals('SKU-1', $shipmentItem->getSku());
        $this->assertEquals((float)'3.5', $shipmentItem->getQty());
        $this->assertEquals($sourceId, $createdShipment->getExtensionAttributes()->getSourceCode());
        $message = $this->transportBuilder->getSentMessage();
        $this->assertEquals("Your store_view_eu_website order is ready for pickup", $message->getSubject());
    }

    /**
     * Get Created Order
     *
     * @return OrderInterface
     */
    private function getCreatedOrder(): OrderInterface
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('increment_id', 'in_store_pickup_test_order')
            ->create();
        /** @var OrderInterface $createdOrder */
        $createdOrder = current($this->orderRepository->getList($searchCriteria)->getItems());
        return $createdOrder;
    }

    /**
     * Get Created Shipment
     *
     * @param int $orderId
     *
     * @return ShipmentInterface
     */
    private function getCreatedShipment(int $orderId): ShipmentInterface
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('order_id', $orderId)
            ->create();
        /** @var ShipmentInterface $createdShipment */
        $createdShipment = current($this->shipmentRepository->getList($searchCriteria)->getItems());
        return $createdShipment;
    }

    /**
     * Set Pickup Location
     *
     * @param OrderInterface $createdOrder
     * @param string $sourceId
     * @return OrderInterface
     */
    private function setPickupLocation(OrderInterface $createdOrder, string $sourceId): OrderInterface
    {
        $extension = $createdOrder->getExtensionAttributes();

        if (empty($extension)) {
            /** @var OrderExtensionInterface $extension */
            $extension = $this->orderExtensionFactory->create();
        }

        $extension->setPickupLocationCode($sourceId);
        $createdOrder->setExtensionAttributes($extension);

        $this->orderRepository->save($createdOrder);

        return $createdOrder;
    }

    /**
     * Data Provider
     *
     * @return array
     */
    public static function dataProvider(): array
    {
        return [
            ['eu-1', 'The order is not ready for pickup'],
            ['eu-3', 'The order is not ready for pickup'],
            ['eu-disabled', 'The order is not ready for pickup'],
            ['us-1', 'The order is not ready for pickup'],
        ];
    }
}
