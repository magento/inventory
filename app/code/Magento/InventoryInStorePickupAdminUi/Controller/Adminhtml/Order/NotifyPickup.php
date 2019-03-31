<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupAdminUi\Controller\Adminhtml\Order;

use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryInStorePickupApi\Api\NotifyOrderIsReadyAndShipInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Class NotifyPickup
 *
 * @package Magento\InventoryInStorePickupAdminUi\Controller\Adminhtml\Order
 */
class NotifyPickup extends \Magento\Backend\App\Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Magento_Sales::emails';

    /**
     * @var \Magento\InventoryInStorePickupApi\Api\NotifyOrderIsReadyAndShipInterface
     */
    private $notifyOrderIsReadyAndShip;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * NotifyPickup constructor.
     *
     * @param \Magento\Backend\App\Action\Context                                       $context
     * @param \Magento\InventoryInStorePickupApi\Api\NotifyOrderIsReadyAndShipInterface $notifyOrderIsReadyAndShip
     * @param \Magento\Sales\Api\OrderRepositoryInterface                               $orderRepository
     * @param \Psr\Log\LoggerInterface                                                  $logger
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        NotifyOrderIsReadyAndShipInterface $notifyOrderIsReadyAndShip,
        OrderRepositoryInterface $orderRepository,
        LoggerInterface $logger
    ) {
        $this->notifyOrderIsReadyAndShip = $notifyOrderIsReadyAndShip;
        $this->orderRepository = $orderRepository;
        $this->logger = $logger;

        parent::__construct($context);
    }

    /**
     * Notify user
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute():\Magento\Framework\Controller\ResultInterface
    {
        $order = $this->initOrder();

        if ($order) {
            try {
                $this->notifyOrderIsReadyAndShip->execute((int)$order->getEntityId());
                $this->messageManager->addSuccessMessage(__('The customer have been notified and shipment created.'));
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            } catch (\Exception $e) {
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
     * @see \Magento\Sales\Controller\Adminhtml\Order::_initOrder
     *
     * @return \Magento\Sales\Api\Data\OrderInterface|false
     */
    private function initOrder()
    {
        $id = $this->getRequest()->getParam('order_id');
        try {
            $order = $this->orderRepository->get($id);
        } catch (NoSuchEntityException $e) {
            $this->messageManager->addErrorMessage(__('This order no longer exists.'));
            $this->_actionFlag->set('', self::FLAG_NO_DISPATCH, true);

            return false;
        } catch (InputException $e) {
            $this->messageManager->addErrorMessage(__('This order no longer exists.'));
            $this->_actionFlag->set('', self::FLAG_NO_DISPATCH, true);

            return false;
        }

        return $order;
    }
}
