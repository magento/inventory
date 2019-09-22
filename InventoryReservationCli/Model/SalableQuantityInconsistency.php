<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryReservationCli\Model;

/**
 * Filter orders for missing initial reservation
 */
class SalableQuantityInconsistency
{
    /**
     * @var int
     */
    private $objectId;

    /**+
     * @var int
     */
    private $stockId;

    /**
     * @var string
     */
    private $orderIncrementId;

    /**
     * @var bool
     */
    private $hasAssignedOrder = false;

    /**
     * @var string
     */
    private $orderStatus;

    /**
     * List of SKUs and quantity
     *
     * @var array
     */
    private $items = [];

    /**
     * Getter for object id
     *
     * @return int
     */
    public function getObjectId(): int
    {
        return $this->objectId;
    }

    /**
     * Setter for object id
     *
     * @param int $objectId
     */
    public function setObjectId(int $objectId): void
    {
        $this->objectId = $objectId;
    }

    /**
     * Adds new item
     *
     * @param string $sku
     * @param float $qty
     */
    public function addItemQty(string $sku, float $qty): void
    {
        if (!isset($this->items[$sku])) {
            $this->items[$sku] = 0.0;
        }
        $this->items[$sku] += $qty;
    }

    /**
     * Getter for items
     *
     * @return array
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * Setter for items
     *
     * @param array $items
     */
    public function setItems(array $items): void
    {
        $this->items = $items;
    }

    /**
     * Getter for stock id
     *
     * @return int
     */
    public function getStockId(): int
    {
        return (int)$this->stockId;
    }

    /**
     * Setter for stock id
     *
     * @param int $stockId
     */
    public function setStockId(int $stockId): void
    {
        $this->stockId = $stockId;
    }

    /**
     * Getter for order increment id
     *
     * @return string
     */
    public function getOrderIncrementId(): string
    {
        return (string)$this->orderIncrementId;
    }

    /**
     * Setter for order increment id
     *
     * @param string $orderIncrementId
     */
    public function setOrderIncrementId(string $orderIncrementId): void
    {
        $this->hasAssignedOrder = true;
        $this->orderIncrementId = $orderIncrementId;
    }

    /**
     * Getter for order state
     *
     * @return string
     */
    public function getOrderStatus(): string
    {
        return (string)$this->orderStatus;
    }

    /**
     * Setter for order status
     *
     * @param string $orderStatus
     */
    public function setOrderStatus(string $orderStatus): void
    {
        $this->orderStatus = $orderStatus;
    }

    /**
     * Retrieve whether an order is assigned to the given inconsistency
     *
     * @return bool
     */
    public function hasAssignedOrder(): bool
    {
        return $this->hasAssignedOrder;
    }
}
