<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupSales\Model;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\InventoryInStorePickupSales\Model\Order\Email\ReadyForPickupSender;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\OrderStatusHistoryRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Asynchronous email sending for "notify order for pickup" handler.
 */
class NotifyOrdersAreReadyForPickupEmailSender
{
    /**
     * @var ReadyForPickupSender
     */
    private $emailSender;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var ScopeConfigInterface
     */
    private $config;

    /**
     * @var OrderStatusHistoryRepositoryInterface
     */
    private $orderStatusHistoryRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ReadyForPickupSender $emailSender
     * @param OrderRepositoryInterface $orderRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param ScopeConfigInterface $config
     * @param OrderStatusHistoryRepositoryInterface $orderStatusHistoryRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        ReadyForPickupSender $emailSender,
        OrderRepositoryInterface $orderRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ScopeConfigInterface $config,
        OrderStatusHistoryRepositoryInterface $orderStatusHistoryRepository,
        LoggerInterface $logger
    ) {
        $this->emailSender = $emailSender;
        $this->orderRepository = $orderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->config = $config;
        $this->orderStatusHistoryRepository = $orderStatusHistoryRepository;
        $this->logger = $logger;
    }

    /**
     * Handles asynchronous email sending for "notify order for pickup".
     *
     * @return void
     */
    public function execute(): void
    {
        if ($this->config->getValue('sales_email/general/async_sending')) {
            $this->searchCriteriaBuilder->addFilter('send_notification', 1);
            $this->searchCriteriaBuilder->addFilter('notification_sent', 0);
            $this->searchCriteriaBuilder->setPageSize(
                (int)$this->config->getValue('sales_email/general/sending_limit')
            );
            $searchCriteria = $this->searchCriteriaBuilder->create();
            $orders = $this->orderRepository->getList($searchCriteria);
            foreach ($orders->getItems() as $order) {
                if ($this->emailSender->send($order, true)) {
                    $this->searchCriteriaBuilder->addFilter('entity_name', 'order');
                    $this->searchCriteriaBuilder->addFilter('is_customer_notified', 0);
                    $this->searchCriteriaBuilder->addFilter('comment', 0);
                    $this->searchCriteriaBuilder->addFilter('parent_id', $order->getEntityId());
                    $searchCriteria = $this->searchCriteriaBuilder->create();
                    $historyItems = $this->orderStatusHistoryRepository->getList($searchCriteria)->getItems();
                    foreach ($historyItems as $historyItem) {
                        $historyItem->setIsCustomerNotified(1);
                        try {
                            $this->orderStatusHistoryRepository->save($historyItem);
                        } catch (CouldNotSaveException $e) {
                            $this->logger->error($e->getLogMessage());
                        }
                    }
                }
            }
        }
    }
}
