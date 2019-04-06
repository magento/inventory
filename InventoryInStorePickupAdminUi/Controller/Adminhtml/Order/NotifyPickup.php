<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupAdminUi\Controller\Adminhtml\Order;

use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryInStorePickupApi\Api\NotifyOrderIsReadyForPickupInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Class NotifyPickup
 *
 * @package Magento\InventoryInStorePickupAdminUi\Controller\Adminhtml\Order
 */
class NotifyPickup extends Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Magento_Sales::emails';

    /**
     * @var NotifyOrderIsReadyForPickupInterface
     */
    private $notifyOrderIsReadyForPickup;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * NotifyPickup constructor.
     *
     * @param Context $context
     * @param NotifyOrderIsReadyForPickupInterface $notifyOrderIsReadyForPickup
     * @param OrderRepositoryInterface $orderRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        NotifyOrderIsReadyForPickupInterface $notifyOrderIsReadyForPickup,
        OrderRepositoryInterface $orderRepository,
        LoggerInterface $logger
    ) {
        $this->notifyOrderIsReadyForPickup = $notifyOrderIsReadyForPickup;
        $this->orderRepository = $orderRepository;
        $this->logger = $logger;

        parent::__construct($context);
    }

    /**
     * Notify user
     *
     * @return ResultInterface
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function execute(): ResultInterface
    {
        $order = $this->initOrder();

        if ($order) {
            try {
                $this->notifyOrderIsReadyForPickup->execute((int)$order->getEntityId());
                $this->messageManager->addSuccessMessage(__('The customer have been notified and shipment created.'));
            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            } catch (Exception $e) {
                $this->messageManager->addErrorMessage(__('We can\'t notify the customer right now.'));
                $this->logger->critical($e);
            }

            return $this->resultRedirectFactory->create()->setPath(
                'sales/order/view',
                [
                    'order_id' => $order->getEntityId()
                ]
            );
        }

        return $this->resultRedirectFactory->create()->setPath('sales/*/');
    }

    /**
     * Initialize order model instance
     *
     * @return OrderInterface
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @see \Magento\Sales\Controller\Adminhtml\Order::_initOrder
     */
    private function initOrder(): OrderInterface
    {
        $id = $this->getRequest()->getParam('order_id');
        try {
            $order = $this->orderRepository->get($id);
        } catch (LocalizedException $e) {
            if ($e instanceof NoSuchEntityException || $e instanceof InputException) {
                $this->messageManager->addErrorMessage(__('This order no longer exists.'));
                $this->_actionFlag->set('', self::FLAG_NO_DISPATCH, true);
            }
            throw $e;
        }

        return $order;
    }
}
