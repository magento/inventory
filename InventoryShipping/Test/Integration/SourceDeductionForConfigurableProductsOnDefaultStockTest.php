<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryShipping\Test\Integration;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Inventory\Model\SourceItem\Command\Handler\SourceItemsSaveHandler;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use PHPUnit\Framework\TestCase;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\InventoryApi\Api\GetSourceItemsBySkuInterface;
use Magento\Sales\Api\ShipOrderInterface;
use Magento\Sales\Api\Data\ShipmentItemCreationInterfaceFactory;
use Magento\Sales\Api\Data\ShipmentItemCreationInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @magentoAppIsolation enabled
 */
class SourceDeductionForConfigurableProductsOnDefaultStockTest extends TestCase
{
    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var GetSourceItemsBySkuInterface
     */
    private $getSourceItemBySku;

    /**
     * @var ShipOrderInterface
     */
    private $shipOrder;

    /**
     * @var ShipmentItemCreationInterfaceFactory
     */
    private $shipmentItemCreationFactory;

    /**
     * @var SourceItemsSaveHandler
     */
    private $sourceItemsSaveHandler;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();

        $this->orderRepository = $this->objectManager->get(OrderRepositoryInterface::class);
        $this->searchCriteriaBuilder = $this->objectManager->get(SearchCriteriaBuilder::class);
        $this->shipmentRepository = $this->objectManager->get(ShipmentRepositoryInterface::class);

        $this->getSourceItemBySku = Bootstrap::getObjectManager()->get(GetSourceItemsBySkuInterface::class);
        $this->shipOrder = Bootstrap::getObjectManager()->get(ShipOrderInterface::class);
        $this->shipmentItemCreationFactory = Bootstrap::getObjectManager()
            ->get(ShipmentItemCreationInterfaceFactory::class);
        $this->sourceItemsSaveHandler = Bootstrap::getObjectManager()
            ->get(SourceItemsSaveHandler::class);
    }

    /**
     * Check that source item deduction don't change stock status from "Out of stock" to "In stock"
     *
     * @magentoDataFixture Magento_InventorySalesApi::Test/_files/websites_with_stores.php
     * @magentoDataFixture Magento_InventoryConfigurableProduct::Test/_files/product_configurable.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/sources.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stocks.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stock_source_links.php
     * @magentoDataFixture Magento_InventorySalesApi::Test/_files/stock_website_sales_channels.php
     * @magentoDataFixture Magento_InventoryConfigurableProduct::Test/_files/source_items_configurable.php
     * @magentoDataFixture Magento_InventoryIndexer::Test/_files/reindex_inventory.php
     * @magentoDataFixture Magento_InventoryShipping::Test/_files/create_quote_on_us_website.php
     * @magentoDataFixture Magento_InventoryShipping::Test/_files/order_configurable_product.php
     *
     * @magentoDbIsolation disabled
     * @return void
     */
    public function testSourceDeduction(): void
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('increment_id', 'created_order_for_test')
            ->create();
        /** @var OrderInterface $order */
        $order = current($this->orderRepository->getList($searchCriteria)->getItems());

        $items = [];
        $sourceItems = [];
        foreach ($order->getItems() as $orderItem) {
            /** @var ShipmentItemCreationInterface $invoiceItemCreation */
            $shipmentItemCreation = $this->shipmentItemCreationFactory->create();
            $shipmentItemCreation->setOrderItemId($orderItem->getItemId());
            $shipmentItemCreation->setQty($orderItem->getQtyOrdered());
            $items[] = $shipmentItemCreation;
            /** @var SourceItemInterface $sourceItem */
            $sourceItem = current($this->getSourceItemBySku->execute($orderItem->getSku()));
            $sourceItem->setStatus(SourceItemInterface::STATUS_OUT_OF_STOCK);
            $sourceItems[] = $sourceItem;
        }
        $this->sourceItemsSaveHandler->execute($sourceItems);

        $this->shipOrder->execute($order->getEntityId(), $items);

        foreach ($sourceItems as $sourceItem) {
            $sourceItemResult = current($this->getSourceItemBySku->execute($sourceItem->getSku()));
            self::assertEquals(SourceItemInterface::STATUS_OUT_OF_STOCK, $sourceItemResult->getStatus());
        }
    }
}
