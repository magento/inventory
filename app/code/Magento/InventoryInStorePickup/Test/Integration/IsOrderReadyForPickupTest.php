<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Test\Integration;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\ObjectManagerInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\InventoryInStorePickupApi\Api\IsOrderReadyForPickupInterface;
use Magento\Sales\Api\Data\OrderExtensionInterface;
use Magento\Sales\Api\Data\OrderExtensionFactory;

class IsOrderReadyForPickupTest extends \PHPUnit\Framework\TestCase
{
    /** @var ObjectManagerInterface */
    private $objectManager;

    /** @var OrderRepositoryInterface */
    private $orderRepository;

    /** @var SearchCriteriaBuilder */
    private $searchCriteriaBuilder;

    /** @var IsOrderReadyForPickupInterface */
    private $isOrderReadyForPickUpService;

    /** @var OrderExtensionInterface */
    private $orderExtensionFactory;

    protected function setUp()
    {
        $this->objectManager = Bootstrap::getObjectManager();

        $this->orderRepository = $this->objectManager->get(OrderRepositoryInterface::class);
        $this->searchCriteriaBuilder = $this->objectManager->get(SearchCriteriaBuilder::class);
        $this->isOrderReadyForPickUpService = $this->objectManager->get(IsOrderReadyForPickupInterface::class);
        $this->orderExtensionFactory = $this->objectManager->get(OrderExtensionFactory::class);
    }

    /**
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/products.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/sources.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stocks.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stock_source_links.php
     * @magentoDataFixture ../../../../app/code/Magento/InventorySalesApi/Test/_files/websites_with_stores.php
     * @magentoDataFixture ../../../../app/code/Magento/InventorySalesApi/Test/_files/stock_website_sales_channels.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/source_items.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryIndexer/Test/_files/reindex_inventory.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryInStorePickup/Test/_files/create_in_store_pickup_quote_on_eu_website.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryInStorePickup/Test/_files/place_order.php
     *
     * @magentoDbIsolation disabled
     * @dataProvider dataProvider
     *
     * @param string $sourceId
     * @param bool $expectedResult
     */
    public function testIsOrderReadyForPickUp(string $sourceId, bool $expectedResult)
    {
        $createdOrder = $this->getCreatedOrder();
        $this->setPickupLocation($createdOrder, $sourceId);

        $orderId = (int)$createdOrder->getEntityId();

        $this->assertEquals($expectedResult, $this->isOrderReadyForPickUpService->execute($orderId), "sourceId: {$sourceId}");
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
    public function dataProvider(): array
    {
        return [
            ['eu-1', true],
            ['eu-2', false],
            ['eu-3', false],

            /**
             * correct statement - but in fact "false positive" due to bug
             * @see \Magento\InventoryInStorePickup\Model\Order\IsFulfillable::isItemFulfillable
             * Search criteria does not contain "isEnabled" filter for sources
             */
            ['eu-disabled', false],

            ['us-1', false],
        ];
    }
}
