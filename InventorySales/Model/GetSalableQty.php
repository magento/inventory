<?php
/**
 * Copyright 2021 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Model;

use Magento\InventoryConfigurationApi\Api\GetStockItemConfigurationInterface;
use Magento\InventoryReservationsApi\Model\GetReservationsQuantityInterface;
use Magento\InventorySalesApi\Model\GetProductAvailableQtyInterface;
use Magento\InventorySalesApi\Model\GetSalableQtyInterface;

/**
 * @inheritdoc
 */
class GetSalableQty implements GetSalableQtyInterface
{
    /**
     * @param GetStockItemConfigurationInterface $getStockItemConfiguration
     * @param GetReservationsQuantityInterface $getReservationsQuantity
     * @param GetProductAvailableQtyInterface $getProductAvailableQty
     */
    public function __construct(
        private readonly GetStockItemConfigurationInterface $getStockItemConfiguration,
        private readonly GetReservationsQuantityInterface $getReservationsQuantity,
        private readonly GetProductAvailableQtyInterface $getProductAvailableQty
    ) {
    }

    /**
     * @inheritdoc
     */
    public function execute(string $sku, int $stockId): float
    {
        $stockItemConfig = $this->getStockItemConfiguration->execute($sku, $stockId);

        return $this->getProductAvailableQty->execute($sku, $stockId)
            + $this->getReservationsQuantity->execute($sku, $stockId)
            - $stockItemConfig->getMinQty();
    }
}
