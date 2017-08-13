<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\InventoryApi\Api\Data;

use Magento\Framework\Api\ExtensibleDataInterface;
use Magento\InventoryApi\Api\Data\ReservationExtensionInterface;

/**
 * The entity responsible for reservations, created to keep inventory amount (product quantity) up-to-date.
 * It is created to have a state between order creation and inventory deduction (deduction of specific SourceItems)
 *
 * @api
 */
interface ReservationInterface extends ExtensibleDataInterface
{
    /**
     * Constants for keys of data array. Identical to the name of the getter in snake case
     */
    const RESERVATION_ID = 'reservation_id';
    const STOCK_ID = 'stock_id';
    const SKU = 'sku';
    const QUANTITY = 'quantity';
    const STATUS = 'status';

    /**#@+
     * Reservation possible statuses. Maybe make sense to introduce extension point for Reservation Open-Close statuses
     */
    const STATUS_ORDER_CREATED = 1; // For Order Placed
    const STATUS_RMA_CREATED = 2; // For RMA Placed

    const STATUS_ORDER_COMPLETE = 101; // For Order Complete
    const STATUS_ORDER_CANCELED = 102; // For Order Canceled
    const STATUS_RMA_COMPLETE = 103; // For RMA Canceled
    /**#@-*/

    /**
     * Get Reservation id
     *
     * @return int|null
     */
    public function getReservationId();

    /**
     * Set Reservation Id
     *
     * @param int $reservationId
     * @return void
     */
    public function setReservationId($reservationId);

    /**
     * Get stock id
     *
     * @param int $stockId
     * @return void
     */
    public function getStockId();

    /**
     * Set stock id
     *
     * @param int $stockId
     * @return void
     */
    public function setStockId($stockId);


    /**
     * Get Product SKU
     *
     * @return string
     */
    public function getSku();

    /**
     * Set product SKU
     *
     * @param string $sku
     * @return void
     */
    public function setSku($sku);

    /**
     * Get Product Qty
     *
     * This value can be positive (>0) or negative (<0) depending on the Reservation Status.
     *
     * For example, when an Order is placed, a Reservation with negative quantity (and STATUS_ORDER_CREATED status) is
     * appended.
     * When that Order is processed and the SourceItems related to ordered products are updated, a Reservation with
     * positive quantity (and STATUS_ORDER_COMPLETE status) is appended to neglect the first one.
     *
     * @return float
     */
    public function getQuantity();

    /**
     * Set Reservation quantity
     *
     * @param float $quantity
     * @return void
     */
    public function setQuantity($quantity);

    /**
     * Get Reservation Status
     *
     * @return int|null
     */
    public function getStatus();

    /**
     * Set Reservation status (One of self::STATUS_*)
     *
     * @param int $status
     * @return void
     */
    public function setStatus($status);

    /**
     * Retrieve existing extension attributes object
     *
     * @return \Magento\InventoryApi\Api\Data\ReservationExtensionInterface|null
     */
    public function getExtensionAttributes();

    /**
     * Set an extension attributes object
     *
     * @param \Magento\InventoryApi\Api\Data\ReservationExtensionInterface $extensionAttributes
     * @return void
     */
    public function setExtensionAttributes(ReservationExtensionInterface $extensionAttributes);
}
