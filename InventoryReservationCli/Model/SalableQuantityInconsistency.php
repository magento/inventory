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
     * @var array
     */
    private $items = [];

    /**
     * @return int
     */
    public function getObjectId(): int
    {
        return $this->objectId;
    }

    /**
     * @param int $objectId
     */
    public function setObjectId(int $objectId): void
    {
        $this->objectId = $objectId;
    }

    /**
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
     * @return array
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * @param array $items
     */
    public function setItems(array $items): void
    {
        $this->items = $items;
    }

    /**
     * @return int
     */
    public function getStockId(): int
    {
        return (int)$this->stockId;
    }

    /**
     * @param int $stockId
     */
    public function setStockId(int $stockId): void
    {
        $this->stockId = $stockId;
    }

    /**
     * @return string
     */
    public function getOrderIncrementId(): string
    {
        return (string)$this->orderIncrementId;
    }

    /**
     * @param string $orderIncrementId
     */
    public function setOrderIncrementId(string $orderIncrementId): void
    {
        $this->hasAssignedOrder = true;
        $this->orderIncrementId = $orderIncrementId;
    }

    /**
     * @return string
     */
    public function getOrderStatus(): string
    {
        return (string)$this->orderStatus;
    }

    /**
     * @param string $orderStatus
     */
    public function setOrderStatus(string $orderStatus): void
    {
        $this->orderStatus = $orderStatus;
    }

    /**
     * @return bool
     */
    public function hasAssignedOrder(): bool
    {
        return $this->hasAssignedOrder;
    }
}
