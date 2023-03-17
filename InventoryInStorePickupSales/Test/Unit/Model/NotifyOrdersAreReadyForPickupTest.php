<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupSales\Test\Unit\Model;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\InventoryInStorePickupSales\Model\NotifyOrdersAreReadyForPickup;
use Magento\InventoryInStorePickupSales\Model\Order\AddStorePickupAttributesToOrder;
use Magento\InventoryInStorePickupSales\Model\Order\CreateShippingDocument;
use Magento\InventoryInStorePickupSales\Model\Order\Email\ReadyForPickupNotifier;
use Magento\InventoryInStorePickupSalesApi\Api\Data\ResultInterface;
use Magento\InventoryInStorePickupSalesApi\Api\Data\ResultInterfaceFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\Sales\Model\Order;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class NotifyOrdersAreReadyForPickupTest extends TestCase
{
    /**
     * @var NotifyOrdersAreReadyForPickup
     */
    private $model;

    /**
     * @var ReadyForPickupNotifier
     */
    private $emailNotifier;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var AddStorePickupAttributesToOrder
     */
    private $addStorePickupAttributesToOrder;

    /**
     * @var ResultInterfaceFactory
     */
    private $resultFactory;

    /**
     * @var ShipmentRepositoryInterface
     */
    private $shipmentRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var CreateShippingDocument
     */
    private $createShippingDocument;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var SearchCriteriaInterface
     */
    private $searchCriteriaInterfaceMock;

    /**
     * @var Order
     */
    private $orderMock;

    /**
     * @var ResultInterface
     */
    private $resultMock;

    protected function setUp(): void
    {
        $this->emailNotifier = $this->getMockBuilder(ReadyForPickupNotifier::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->orderRepository = $this->getMockBuilder(OrderRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->addStorePickupAttributesToOrder = $this->getMockBuilder(AddStorePickupAttributesToOrder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultFactory = $this->getMockBuilder(ResultInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->shipmentRepository = $this->getMockBuilder(ShipmentRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getList','get','delete','save','create'])
            ->addMethods([
                'getTotalCount'
            ])
            ->getMock();
        $this->searchCriteriaBuilder = $this->getMockBuilder(SearchCriteriaBuilder::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create','addFilter'])
            ->getMock();
        $this->createShippingDocument = $this->getMockBuilder(CreateShippingDocument::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->model = new NotifyOrdersAreReadyForPickup(
            $this->emailNotifier,
            $this->orderRepository,
            $this->addStorePickupAttributesToOrder,
            $this->resultFactory,
            $this->shipmentRepository,
            $this->searchCriteriaBuilder,
            $this->createShippingDocument,
            $this->logger
        );
        $this->searchCriteriaInterfaceMock = $this->getMockBuilder(SearchCriteriaInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->orderMock = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultMock = $this->getMockBuilder(ResultInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @dataProvider executeMethodEmailCheck
     * @param $exception
     * @return void
     */
    public function testExecuteForEmailNotify($exception): void
    {
        $this->orderMock->method('getExtensionAttributes')->willReturnSelf();
        $this->orderRepository->method('get')->willReturn($this->orderMock);
        $this->searchCriteriaBuilder->method('addFilter')->willReturnSelf();
        $this->searchCriteriaBuilder->method('create')->willReturn($this->searchCriteriaInterfaceMock);
        $this->shipmentRepository->method('getList')->willReturnSelf();
        $this->resultFactory->method('create')->willReturn($this->resultMock);
        if ($exception) {
            $this->createShippingDocument->method('execute')->willThrowException(
                new \Exception("Error")
            );
            $this->emailNotifier->expects($this->never())->method('notify');
        } else {
            $this->emailNotifier->expects($this->once())->method('notify');
        }
        $this->model->execute([1]);
    }

    /**
     * @return array
     */
    public function executeMethodEmailCheck(): array
    {
        return [
            ['with_exception' => true],
            ['without_exception' => false]
        ];
    }
}
