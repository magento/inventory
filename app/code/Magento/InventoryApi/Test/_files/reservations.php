<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

use Magento\InventoryApi\Api\ReservationBuilderInterface;
use Magento\InventoryApi\Api\ReservationsAppendInterface;
use Magento\TestFramework\Helper\Bootstrap;

$reservationBuilder = Bootstrap::getObjectManager()->get(ReservationBuilderInterface::class);
$reservationsAppend = Bootstrap::getObjectManager()->get(ReservationsAppendInterface::class);

$reservationsAppend->execute([
    // reserve 1.7 units for SKU-1
    $reservationBuilder->setStockId(1)->setSku('SKU-1')->setQuantity(-1.7)->build(),

    // release 1 unit for SKU-1
    $reservationBuilder->setStockId(1)->setSku('SKU-1')->setQuantity(1)->build(),

    // reserve 5.5 units for SKU-2
    $reservationBuilder->setStockId(1)->setSku('SKU-2')->setQuantity(-5.5)->build(),
]);
