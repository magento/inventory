<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);
namespace Magento\InventorySales\Model\StockManager;

use Magento\Inventory\Model\Reservation\Command\ReservationsAppend;
use Magento\Inventory\Model\Reservation\ReservationBuilder;

class ReservationsRegister
{
    /**
     * @var ReservationBuilder
     */
    private $reservationBuilder;

    /**
     * @var ReservationsAppend
     */
    private $reservationsAppend;

    /**
     * ReservationsRegister constructor.
     *
     * @param ReservationBuilder $reservationBuilder
     * @param ReservationsAppend $reservationsAppend
     */
    public function __construct(
        ReservationBuilder $reservationBuilder,
        ReservationsAppend $reservationsAppend
    ) {
        $this->reservationBuilder = $reservationBuilder;
        $this->reservationsAppend = $reservationsAppend;
    }

    /**
     * @param float[] $productsQtyBySku array of products, where key is a SKU, and value is a qty
     * @param int $stockId
     * @return void
     */
    public function execute(array $productsQtyBySku, int $stockId)
    {
        $reservations = [];
        foreach ($productsQtyBySku as $sku => $qty) {
            $reservations[] = $this->reservationBuilder
                ->setSku($sku)
                ->setQuantity($qty)
                ->setStockId($stockId)
                ->build();
        }
        $this->reservationsAppend->execute($reservations);
    }
}
