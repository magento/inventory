<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Model;

use Magento\InventoryConfigurationApi\Api\GetStockItemConfigurationInterface;
use Magento\InventoryReservationsApi\Model\GetReservationsQuantityInterface;
use Magento\InventorySalesApi\Model\GetSalableQtyInterface;
use Magento\InventorySalesApi\Model\GetStockItemDataInterface;

/**
 * @inheritdoc
 */
class GetSalableQty implements GetSalableQtyInterface
{
    /**
     * @var GetStockItemConfigurationInterface
     */
    private $getStockItemConfiguration;

    /**
     * @var GetReservationsQuantityInterface
     */
    private $getReservationsQuantity;

    /**
     * @var GetStockItemDataInterface
     */
    private $getProductAvailableQty;

    /**
     * @param GetStockItemConfigurationInterface $getStockItemConfig
     * @param GetReservationsQuantityInterface $getReservationsQuantity
     * @param GetProductAvailableQty $getProductAvailableQty
     */
    public function __construct(
        GetStockItemConfigurationInterface $getStockItemConfig,
        GetReservationsQuantityInterface $getReservationsQuantity,
        GetProductAvailableQty $getProductAvailableQty
    ) {
        $this->getStockItemConfiguration = $getStockItemConfig;
        $this->getReservationsQuantity = $getReservationsQuantity;
        $this->getProductAvailableQty = $getProductAvailableQty;
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
