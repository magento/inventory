<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Plugin\AsyncOrder\Model\OrderManagement;

use Magento\InventorySales\Model\AppendReservations;
use Magento\InventorySales\Plugin\Sales\OrderManagement\AppendReservationsAfterOrderPlacementPlugin;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\Data\SalesEventExtensionInterface;
use Magento\InventorySalesApi\Api\Data\SalesEventInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote as QuoteEntity;
use Magento\Quote\Model\ResourceModel\Quote\Item;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

class AppendReservationsAfterAsyncOrderRejectionPlugin
{
    /**
     * @param AppendReservations
     */
    private AppendReservations $appendReservations;

    public const STATUS_REJECTED = 'rejected';

    /**
     * @param AppendReservations $appendReservations
     */
    public function __construct(
        AppendReservations $appendReservations
    ) {
        $this->appendReservations = $appendReservations;
    }

    /**
     * Add reservation after rejecting Async order
     *
     * @param OrderRepositoryInterface $subject
     * @param OrderInterface $result
     * @return OrderInterface $result
     * @throws \Exception
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterSave(
        OrderRepositoryInterface $subject,
        OrderInterface $result
    ): OrderInterface {
        if ($result->getStatus() === self::STATUS_REJECTED) {
            $this->appendReservations->execute($result);
        }
        return $result;
    }
}
