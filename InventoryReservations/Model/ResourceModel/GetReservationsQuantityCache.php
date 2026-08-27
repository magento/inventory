<?php
/**
 * Copyright 2019 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryReservations\Model\ResourceModel;

use Magento\InventoryReservations\Model\GetReservationsQuantity\CacheStorage;
use Magento\InventoryReservationsApi\Model\GetReservationsQuantityInterface;

/**
 * @inheritdoc
 */
class GetReservationsQuantityCache implements GetReservationsQuantityInterface
{
    /**
     * @param GetReservationsQuantity $getReservationsQuantity
     * @param CacheStorage $reservationsQuantityCacheStorage
     * @param bool $isReadonly
     */
    public function __construct(
        private readonly GetReservationsQuantity $getReservationsQuantity,
        private readonly CacheStorage $reservationsQuantityCacheStorage,
        private readonly bool $isReadonly = false
    ) {
    }

    /**
     * @inheritdoc
     */
    public function execute(string $sku, int $stockId): float
    {
        if (!$this->reservationsQuantityCacheStorage->has($sku, $stockId)) {
            $value = $this->getReservationsQuantity->execute($sku, $stockId);
            if (!$this->isReadonly) {
                $this->reservationsQuantityCacheStorage->set($sku, $stockId, $value);
            }
        } else {
            $value = $this->reservationsQuantityCacheStorage->get($sku, $stockId);
        }

        return $value;
    }
}
