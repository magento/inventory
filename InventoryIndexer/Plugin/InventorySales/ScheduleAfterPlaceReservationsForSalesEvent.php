<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Plugin\InventorySales;

use Magento\InventorySales\Model\PlaceReservationsForSalesEvent;
use Magento\Framework\MessageQueue\PublisherInterface;

/**
 * Invalidate Inventory Indexer after Source was enabled or disabled.
 */
class ScheduleAfterPlaceReservationsForSalesEvent
{

    public const TOPIC_RESERVATION_PLACED = "inventory.reservation.place";

    /**
     * @var PublisherInterface
     */
    private $publisher;

    /**
     * @param PublisherInterface $publisher
     */
    public function __construct(
        PublisherInterface $publisher
    ) {
        $this->publisher = $publisher;
    }

    /**
     * Schedule stock status update after resevation placed
     *
     * @param PlaceReservationsForSalesEvent $subject
     * @param void $result
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterExecute(
        PlaceReservationsForSalesEvent $subject,
        $result
    ) {
        $this->publisher->publish(self::TOPIC_RESERVATION_PLACED, []);
    }
}
