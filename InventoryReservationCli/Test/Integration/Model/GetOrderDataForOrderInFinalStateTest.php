<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryReservationCli\Test\Integration\Model;

use Magento\Framework\ObjectManagerInterface;
use Magento\InventoryReservationCli\Model\ResourceModel\GetOrderDataForOrderInFinalState;
use Magento\Sales\Api\Data\OrderInterfaceFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class GetOrderDataForOrderInFinalStateTest extends TestCase
{
    /**
     * @var GetOrderDataForOrderInFinalState
     */
    private $model;

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * Initialize test dependencies
     */
    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->model = $this->objectManager->get(GetOrderDataForOrderInFinalState::class);
    }

    /**
     * Test GetOrderDataForOrdersInFinalState
     *
     * The purpose of the test is to check that Orders that don't contain store id
     * should not be loaded in the list after the Store related to the Order was removed.
     *
     * @magentoDataFixture Magento/Store/_files/second_website_with_store_group_and_store.php
     * @magentoDataFixture Magento/Sales/_files/order_on_second_website.php
     * @magentoAppArea adminhtml
     * @magentoDbIsolation disabled
     * @magentoAppIsolation enabled
     */
    public function testExecute(): void
    {
         /* Deleting the custom Store before the test is completed can cause random fails for other tests in batch,
            so we remove store id of the Order to imitate deleting related Store */
        $orderIncrementId = '100000001';
        /** @var OrderRepositoryInterface $orderRepository */
        $orderRepository = $this->objectManager->get(OrderRepositoryInterface::class);
        /** @var \Magento\Sales\Api\Data\OrderInterface $order */
        $order = $this->objectManager->get(OrderInterfaceFactory::class)->create()->loadByIncrementId($orderIncrementId);
        $order->setState('complete');
        $order->setStoreId(null);
        $orderRepository->save($order);

        $result = $this->model->execute([], [$orderIncrementId]);
        $this->assertEmpty($result, 'No Orders should be loaded');
    }
}
