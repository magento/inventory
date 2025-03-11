<?php
/**
 * Copyright 2017 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryReservationsApi\Model;

use Magento\Framework\Validation\ValidationException;
use Magento\InventoryReservationsApi\Model\ReservationInterface;

/**
 * Used to build ReservationInterface objects
 *
 * @api
 * @see ReservationInterface
 */
interface ReservationBuilderInterface
{
    /**
     * Set stock id
     *
     * @param int $stockId
     * @return self
     */
    public function setStockId(int $stockId): self;

    /**
     * Set SKU
     *
     * @param string $sku
     * @return self
     */
    public function setSku(string $sku): self;

    /**
     * Set quantity
     *
     * @param float $quantity
     * @return self
     */
    public function setQuantity(float $quantity): self;

    /**
     * Set metadata
     *
     * @param string|null $metadata
     * @return self
     */
    public function setMetadata(?string $metadata = null): self;

    /**
     * Method build for reservation
     *
     * @return ReservationInterface
     * @throws ValidationException
     */
    public function build(): ReservationInterface;
}
