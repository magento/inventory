<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model\Order\Email;

use Magento\Framework\DataObject;

/**
 * Class ReadyForPickupSender
 *
 * @package Magento\InventoryInStorePickup\Model\Order\Email
 * TODO: refactor
 * TODO: Implement asynchronous email sending
 */
class ReadyForPickupSender extends \Magento\Sales\Model\Order\Email\Sender
{
    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    private $eventManager;

    /**
     * ReadyForPickupSender constructor.
     *
     * @param \Magento\Sales\Model\Order\Email\Container\Template          $templateContainer
     * @param \Magento\Sales\Model\Order\Email\Container\IdentityInterface $identityContainer
     * @param \Magento\Sales\Model\Order\Email\SenderBuilderFactory        $senderBuilderFactory
     * @param \Psr\Log\LoggerInterface                                     $logger
     * @param \Magento\Sales\Model\Order\Address\Renderer                  $addressRenderer
     * @param \Magento\Framework\Event\ManagerInterface                    $eventManager
     */
    public function __construct(
        \Magento\Sales\Model\Order\Email\Container\Template $templateContainer,
        \Magento\Sales\Model\Order\Email\Container\IdentityInterface $identityContainer,
        \Magento\Sales\Model\Order\Email\SenderBuilderFactory $senderBuilderFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Sales\Model\Order\Address\Renderer $addressRenderer,
        \Magento\Framework\Event\ManagerInterface $eventManager
    ) {
        parent::__construct($templateContainer, $identityContainer, $senderBuilderFactory, $logger, $addressRenderer);

        $this->eventManager = $eventManager;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     *
     * @return bool
     */
    public function send(\Magento\Sales\Model\Order $order):bool
    {
        return $this->checkAndSend($order);
    }

    /**
     * Prepare email template with variables
     *
     * @param \Magento\Sales\Model\ $order
     *
     * @return void
     */
    protected function prepareTemplate(\Magento\Sales\Model\Order $order)
    {
        $transport = [
            'order'                    => $order,
            'store'                    => $order->getStore(),
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
