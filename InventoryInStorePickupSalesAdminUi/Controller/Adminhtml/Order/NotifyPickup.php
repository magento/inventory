<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupSalesAdminUi\Controller\Adminhtml\Order;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryInStorePickupSalesApi\Api\NotifyOrdersAreReadyForPickupInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Notify Customer of order pickup availability.
 */
class NotifyPickup extends Action implements HttpGetActionInterface
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Magento_Sales::emails';

    /**
     * @var NotifyOrdersAreReadyForPickupInterface
     */
    private $notifyOrdersAreReadyForPickup;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param Context $context
     * @param NotifyOrdersAreReadyForPickupInterface $notifyOrdersAreReadyForPickup
     * @param OrderRepositoryInterface $orderRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        NotifyOrdersAreReadyForPickupInterface $notifyOrdersAreReadyForPickup,
        OrderRepositoryInterface $orderRepository,
        LoggerInterface $logger
    ) {
        $this->notifyOrdersAreReadyForPickup = $notifyOrdersAreReadyForPickup;
        $this->orderRepository = $orderRepository;
        $this->logger = $logger;

        parent::__construct($context);
    }

    /**
     * Notify customer by email
     *
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        try {
            $order = $this->initOrder();
        } catch (LocalizedException $e) {
            return $this->resultRedirectFactory->create()->setPath('sales/*/');
        }

        $result = $this->notifyOrdersAreReadyForPickup->execute([(int)$order->getEntityId()]);
        if ($result->isSuccessful()) {
            $this->messageManager->addSuccessMessage(__('The customer has been notified and shipment created.'));
        } else {
            $error = current($result->getErrors());
            $this->messageManager->addErrorMessage($error['message']);
        }

        return $this->resultRedirectFactory->create()->setPath(
            'sales/order/view',
            [
                'order_id' => $order->getEntityId(),
            ]
        );
    }

    /**
     * Initialize order model instance
     *
     * @return OrderInterface
     * @throws InputException
     * @throws NoSuchEntityException
     * @see \Magento\Sales\Controller\Adminhtml\Order::_initOrder
     */
    private function initOrder(): OrderInterface
    {
        $id = $this->getRequest()->getParam('order_id');
        try {
            $order = $this->orderRepository->get($id);
        } catch (NoSuchEntityException|InputException $e) {
            $this->messageManager->addErrorMessage(__('This order no longer exists.'));
            $this->_actionFlag->set('', self::FLAG_NO_DISPATCH, true);
            throw $e;
        }

        return $order;
    }
}
