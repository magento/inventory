<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupSales\Model\Order\Email;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Event\ManagerInterface;
use Magento\InventoryInStorePickupSales\Model\ResourceModel\OrderNotificationSent\SaveOrderNotificationSent;
use Magento\InventoryInStorePickupSales\Model\ResourceModel\OrderSendNotification\SaveOrderSendNotification;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Address\Renderer;
use Magento\Sales\Model\Order\Email\Container\IdentityInterface;
use Magento\Sales\Model\Order\Email\Container\Template;
use Magento\Sales\Model\Order\Email\Sender;
use Magento\Sales\Model\Order\Email\SenderBuilderFactory;
use Psr\Log\LoggerInterface;

/**
 * @inheritdoc
 */
class ReadyForPickupSender extends Sender
{
    /**
     * @var ManagerInterface
     */
    private $eventManager;

    /**
     * @var ScopeConfigInterface
     */
    private $config;

    /**
     * @var SaveOrderNotificationSent
     */
    private $saveOrderNotificationSent;

    /**
     * @var SaveOrderSendNotification
     */
    private $saveOrderSendNotification;

    /**
     * @param Template $templateContainer
     * @param IdentityInterface $identityContainer
     * @param SenderBuilderFactory $senderBuilderFactory
     * @param LoggerInterface $logger
     * @param Renderer $addressRenderer
     * @param ManagerInterface $eventManager
     * @param ScopeConfigInterface $config
     * @param SaveOrderNotificationSent $saveOrderNotificationSent
     * @param SaveOrderSendNotification $saveOrderSendNotification
     */
    public function __construct(
        Template $templateContainer,
        IdentityInterface $identityContainer,
        SenderBuilderFactory $senderBuilderFactory,
        LoggerInterface $logger,
        Renderer $addressRenderer,
        ManagerInterface $eventManager,
        ScopeConfigInterface $config,
        SaveOrderNotificationSent $saveOrderNotificationSent,
        SaveOrderSendNotification $saveOrderSendNotification
    ) {
        parent::__construct($templateContainer, $identityContainer, $senderBuilderFactory, $logger, $addressRenderer);

        $this->eventManager = $eventManager;
        $this->config = $config;
        $this->saveOrderNotificationSent = $saveOrderNotificationSent;
        $this->saveOrderSendNotification = $saveOrderSendNotification;
    }

    /**
     * Send order-specific email.
     *
     * This method is not declared anywhere in parent/interface, but Magento calls it.
     *
     * @param OrderInterface $order
     * @param bool $forceSyncMode
     * @return bool
     */
    public function send(OrderInterface $order, $forceSyncMode = false): bool
    {
        $result = false;
        $isEnabled = (int)$this->identityContainer->isEnabled();
        $order->getExtensionAttributes()->setSendNotification($isEnabled);
        $this->saveOrderSendNotification->execute((int)$order->getEntityId(), $isEnabled);
        $order->getExtensionAttributes()->setNotificationSent(0);
        if (!$this->config->getValue('sales_email/general/async_sending' || $forceSyncMode)) {
            $result = $this->checkAndSend($order);
            $order->getExtensionAttributes()->setNotificationSent((int)$result);
        }
        $this->saveOrderNotificationSent->execute(
            (int)$order->getEntityId(),
            $order->getExtensionAttributes()->getNotificationSent()
        );

        return $result;
    }

    /**
     * Prepare email template with variables
     *
     * @param Order $order
     * @return void
     */
    protected function prepareTemplate(Order $order)
    {
        $transport = [
            'order' => $order,
            'store' => $order->getStore(),
            'formattedShippingAddress' => $this->getFormattedShippingAddress($order),
        ];
        $transportObject = new DataObject($transport);

        /**
         * Event argument `transport` is @deprecated. Use `transportObject` instead.
         */
        $this->eventManager->dispatch(
            'email_ready_for_pickup_set_template_vars_before',
            ['sender' => $this, 'transport' => $transportObject, 'transportObject' => $transportObject]
        );

        $this->templateContainer->setTemplateVars($transportObject->getData());

        parent::prepareTemplate($order);
    }
}
