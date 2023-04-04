<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupSales\Model;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\LocalizedException;
use Magento\InventoryInStorePickupSales\Model\Order\AddStorePickupAttributesToOrder;
use Magento\InventoryInStorePickupSales\Model\Order\CreateShippingDocument;
use Magento\InventoryInStorePickupSales\Model\Order\Email\ReadyForPickupNotifier;
use Magento\InventoryInStorePickupSalesApi\Api\NotifyOrdersAreReadyForPickupInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\InventoryInStorePickupSalesApi\Api\Data\ResultInterface;
use Magento\InventoryInStorePickupSalesApi\Api\Data\ResultInterfaceFactory;
use Psr\Log\LoggerInterface;

/**
 * Send an email to the customer and ship the order to reserve (deduct) pickup location`s QTY..
 */
class NotifyOrdersAreReadyForPickup implements NotifyOrdersAreReadyForPickupInterface
{
    /**
     * @var ReadyForPickupNotifier
     */
    private $emailNotifier;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var Order\AddStorePickupAttributesToOrder
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
     * @param ReadyForPickupNotifier $emailNotifier
     * @param OrderRepositoryInterface $orderRepository
     * @param AddStorePickupAttributesToOrder $addStorePickupAttributesToOrder
     * @param ResultInterfaceFactory $resultFactory
     * @param ShipmentRepositoryInterface $shipmentRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param CreateShippingDocument $createShippingDocument
     * @param LoggerInterface $logger
     */
    public function __construct(
        ReadyForPickupNotifier $emailNotifier,
        OrderRepositoryInterface $orderRepository,
        AddStorePickupAttributesToOrder $addStorePickupAttributesToOrder,
        ResultInterfaceFactory $resultFactory,
        ShipmentRepositoryInterface $shipmentRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        CreateShippingDocument $createShippingDocument,
        LoggerInterface $logger
    ) {
        $this->emailNotifier = $emailNotifier;
        $this->orderRepository = $orderRepository;
        $this->addStorePickupAttributesToOrder = $addStorePickupAttributesToOrder;
        $this->resultFactory = $resultFactory;
        $this->shipmentRepository = $shipmentRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->createShippingDocument = $createShippingDocument;
        $this->logger = $logger;
    }

    /**
     * Send an email to the customer and ship the order to reserve (deduct) pickup location`s QTY.
     *
     * Notify customer that the order is ready for pickup by sending notification email. Ship the order to deduct the
     * item quantity from the appropriate source.
     *
     * @inheritdoc
     */
    public function execute(array $orderIds): ResultInterface
    {
        $errors = [];
        foreach ($orderIds as $orderId) {
            try {
                $order = $this->orderRepository->get($orderId);
                $searchCriteria = $this->searchCriteriaBuilder->addFilter('order_id', $orderId);
                $shipments = $this->shipmentRepository->getList($searchCriteria->create());
                $isShipmentCreated = $shipments->getTotalCount() > 0;
                if ($isShipmentCreated === false) {
                    $order->getExtensionAttributes()->setSendNotification(0);
                    $this->createShippingDocument->execute($order);
                }
                $this->addStorePickupAttributesToOrder->execute($order);
                $this->emailNotifier->notify($order);
            } catch (LocalizedException $exception) {
                $errors[] = [
                    'id' => $orderId,
                    'message' => $exception->getMessage(),
                ];
                $this->logger->critical($exception);
                continue;
            } catch (\Exception $exception) {
                $errors[] = [
                    'id' => $orderId,
                    'message' => 'We can\'t notify the customer right now.',
                ];
                $this->logger->critical($exception);
                continue;
            }
        }

        return $this->resultFactory->create(['errors' => $errors]);
    }
}
