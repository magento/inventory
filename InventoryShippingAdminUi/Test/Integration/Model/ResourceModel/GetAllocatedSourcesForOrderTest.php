<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryShippingAdminUi\Test\Integration\Model\ResourceModel;

use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\InventoryShippingAdminUi\Model\ResourceModel\GetAllocatedSourcesForOrder;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\OrderRepository;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * Test for \Magento\InventoryShippingAdminUi\Model\ResourceModel\GetAllocatedSourcesForOrder
 */
class GetAllocatedSourcesForOrderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var OrderRepository
     */
    private $orderRepository;

    /**
     * @var GetAllocatedSourcesForOrder
     */
    private $model;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->orderRepository = $this->objectManager->get(OrderRepository::class);

        $this->model = $this->objectManager->get(GetAllocatedSourcesForOrder::class);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/shipment.php
     */
    public function testExecute(): void
    {
        /** @var SearchCriteria $searchCriteria */
        $searchCriteria = $this->objectManager->create(SearchCriteriaBuilder::class)
            ->addFilter(OrderInterface::INCREMENT_ID, '100000001')
            ->setPageSize(1)
            ->setCurrentPage(1)
            ->create();

        $orders = $this->orderRepository->getList($searchCriteria)->getItems();
        /** @var OrderInterface|null $order */
        $order = reset($orders);
        if ($order->getId()) {
            $expected = ['Default Source'];
            $result = $this->model->execute((int)$order->getId());
            $this->assertEquals($expected, $result, 'The source doesn\'t exist');
        } else {
            $this->fail(__('The order doesn\'t exist.'));
        }
    }
}
