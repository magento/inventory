<?php
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryReservations\Cron;

use Magento\InventoryReservationsApi\Model\CleanupReservationsInterface;

/**
 * Cron job precessing of reservations cleanup
 */
class CleanupReservations
{
    /**
     * @var CleanupReservationsInterface
     */
    private $cleanupReservations;

    /**
     * @param CleanupReservationsInterface $cleanupReservations
     */
    public function __construct(CleanupReservationsInterface $cleanupReservations)
    {
        $this->cleanupReservations = $cleanupReservations;
    }

    /**
     * Cleanup reservations
     *
     * @return void
     */
    public function execute()
    {
        $this->cleanupReservations->execute();
    }
}
