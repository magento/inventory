<?php
/**
 * Copyright 2024 Adobe
 * All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryShippingAdminUi\Test\Integration;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\ShipOrderInterface;
use Magento\Sales\Api\Data\ShipmentItemCreationInterfaceFactory;
use Magento\Sales\Api\Data\ShipmentItemCreationInterface;
use Magento\InventoryShipping\Model\ResourceModel\ShipmentSource\SaveShipmentSource;
use Magento\InventoryShippingAdminUi\Model\ResourceModel\GetAllocatedSourcesForOrder;
use Magento\Sales\Controller\Adminhtml\Order\ExportBase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @magentoAppIsolation enabled
 */
class ExportOrderWithAllocatedSource extends ExportBase
{
    /**
     * @var SaveShipmentSource
     */
    private $saveShipmentSource;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var ShipOrderInterface
     */
    private $shipOrder;

    /**
     * @var ShipmentItemCreationInterfaceFactory
     */
    private $shipmentItemCreationFactory;

    /**
     * @var GetAllocatedSourcesForOrder
     */
    private $getAllocatedSourcesForOrder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->orderRepository = $this->_objectManager->get(OrderRepositoryInterface::class);
        $this->searchCriteriaBuilder = $this->_objectManager->get(SearchCriteriaBuilder::class);
        $this->shipOrder = $this->_objectManager->get(ShipOrderInterface::class);
        $this->shipmentItemCreationFactory = $this->_objectManager
            ->get(ShipmentItemCreationInterfaceFactory::class);
        $this->saveShipmentSource = $this->_objectManager
            ->get(SaveShipmentSource::class);
        $this->getAllocatedSourcesForOrder = $this->_objectManager
            ->get(GetAllocatedSourcesForOrder::class);
    }

    /**
     * Check that allocated source value exist in export csv
     *
     * @magentoDataFixture Magento_InventoryApi::Test/_files/sources.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stocks.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stock_source_links.php
     * @magentoDataFixture Magento_InventorySalesApi::Test/_files/websites_with_stores.php
     * @magentoDataFixture Magento/Checkout/_files/simple_product.php
     * @magentoDataFixture Magento_InventoryShipping::Test/_files/source_items_for_simple_on_multi_source.php
     * @magentoDataFixture Magento_InventoryIndexer::Test/_files/reindex_inventory.php
     * @magentoDataFixture Magento_InventoryShipping::Test/_files/create_quote_on_eu_website.php
     * @magentoDataFixture Magento_InventoryShipping::Test/_files/order_simple_product.php
     *
     * @magentoDbIsolation disabled
     *
     * @dataProvider exportOrderDataProvider
     * @param string $format
     * @return void
     */
    public function testExportAllocatedSourceValue(string $format): void
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('increment_id', 'created_order_for_test')
            ->create();
        /** @var OrderInterface $order */
        $order = current($this->orderRepository->getList($searchCriteria)->getItems());

        $items1 = [];
        $items2 = [];

        foreach ($order->getItems() as $orderItem) {
            /** @var ShipmentItemCreationInterface $invoiceItemCreation */
            $shipmentItemCreation1 = $this->shipmentItemCreationFactory->create();
            $shipmentItemCreation1->setOrderItemId($orderItem->getItemId());
            $shipmentItemCreation1->setQty(2);
            $items1[] = $shipmentItemCreation1;

            $shipmentItemCreation2 = $this->shipmentItemCreationFactory->create();
            $shipmentItemCreation2->setOrderItemId($orderItem->getItemId());
            $shipmentItemCreation2->setQty(1);
            $items2[] = $shipmentItemCreation2;
        }

        $shipId1 = $this->shipOrder->execute($order->getEntityId(), $items1);
        $this->saveShipmentSource->execute((int)$shipId1, 'eu-1');

        $shipId2 = $this->shipOrder->execute($order->getEntityId(), $items2);
        $this->saveShipmentSource->execute((int)$shipId2, 'eu-2');

        $allocated_sources = $this->getAllocatedSourcesForOrder->execute((int)$order->getEntityId());
        $allocated_sources = implode(",", $allocated_sources);

        $url = $this->getExportUrl($format, null);
        $response = $this->dispatchExport(
            $url,
            ['namespace' => 'sales_order_grid', 'filters' => ['increment_id' => 'created_order_for_test']]
        );
        $orders = $this->parseResponse($format, $response);
        $exportedOrder = reset($orders);
        $this->assertNotFalse($exportedOrder);
        $this->assertEquals(
            $allocated_sources,
            $exportedOrder['Allocated sources']
        );
    }

    /**
     * @return array
     */
    public static function exportOrderDataProvider(): array
    {
        return [
            'order_grid_in_csv' => ['format' => ExportBase::CSV_FORMAT],
            'order_grid_in_xml' => ['format' => ExportBase::XML_FORMAT],
        ];
    }
}
