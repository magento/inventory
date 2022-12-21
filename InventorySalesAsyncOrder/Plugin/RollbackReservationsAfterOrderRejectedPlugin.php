<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySalesAsyncOrder\Plugin;

use Magento\AsyncOrder\Model\Order\Email\Sender\RejectedOrderSender;
use Magento\AsyncOrder\Model\OrderRejecter;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventorySalesAsyncOrder\Model\ReservationExecution;
use Magento\InventorySalesAsyncOrder\Model\Reservations;
use Magento\Sales\Api\Data\OrderInterface;

class RollbackReservationsAfterOrderRejectedPlugin
{
    /**
     * @var Reservations
     */
    private Reservations $appendReservations;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @param Reservations $appendReservations
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        Reservations $appendReservations,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->appendReservations = $appendReservations;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Rollback reservations after async order is rejected and stock update is not deferred.
     *
     * @param RejectedOrderSender $subject
     * @param OrderInterface $order
     * @param bool $notify
     * @param string $comment
     * @return void
     * @throws NoSuchEntityException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeSend(
        RejectedOrderSender $subject,
        OrderInterface $order,
        bool $notify = true,
        string $comment = ''
    ): void {
        if ($order->getStatus() === OrderRejecter::STATUS_REJECTED
            && !$this->scopeConfig->isSetFlag(ReservationExecution::CONFIG_PATH_USE_DEFERRED_STOCK_UPDATE)
        ) {
            $this->appendReservations->execute($order);
        }
    }
}
