<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySalesAsyncOrder\Plugin;

use Magento\AsyncOrder\Model\Order\Email\Sender\RejectedOrderSender;
use Magento\AsyncOrder\Model\OrderRejecter;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventorySalesAsyncOrder\Model\Reservations;
use Magento\Sales\Api\Data\OrderInterface;

class RollbackReservationsAfterOrderRejectedPlugin
{
    /**
     * @var Reservations
     */
    private Reservations $appendReservations;

    /**
     * @param Reservations $appendReservations
     */
    public function __construct(
        Reservations $appendReservations
    ) {
        $this->appendReservations = $appendReservations;
    }

    /**
     * Rollback reservations after async order is rejected.
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
        if ($order->getStatus() === OrderRejecter::STATUS_REJECTED) {
            $this->appendReservations->execute($order);
        }
    }
}
