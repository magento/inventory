<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

use Magento\Inventory\Model\ReservationCleanupInterface;
use Magento\InventoryApi\Api\ReservationBuilderInterface;
use Magento\InventoryApi\Api\ReservationsAppendInterface;
use Magento\TestFramework\Helper\Bootstrap;

$reservationBuilder = Bootstrap::getObjectManager()->get(ReservationBuilderInterface::class);
$reservationsAppend = Bootstrap::getObjectManager()->get(ReservationsAppendInterface::class);
$reservationCleanup = Bootstrap::getObjectManager()->create(ReservationCleanupInterface::class);

$reservationsAppend->execute([
    // release 0.7 units from reservations
    $reservationBuilder->setStockId(1)->setSku('SKU-1')->setQuantity(0.7)->build(),

    // release 5.5 units for SKU-2
    $reservationBuilder->setStockId(1)->setSku('SKU-2')->setQuantity(5.5)->build(),
]);

$reservationCleanup->execute();
