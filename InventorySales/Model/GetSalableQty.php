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
     * @var GetStockItemDataInterface
     */
    private $getStockItemData;

    /**
     * @var GetReservationsQuantityInterface
     */
    private $getReservationsQuantity;

    /**
     * @param GetStockItemConfigurationInterface $getStockItemConfig
     * @param GetStockItemDataInterface $getStockItemData
     * @param GetReservationsQuantityInterface $getReservationsQuantity
     */
    public function __construct(
        GetStockItemConfigurationInterface $getStockItemConfig,
        GetStockItemDataInterface $getStockItemData,
        GetReservationsQuantityInterface $getReservationsQuantity
    ) {
        $this->getStockItemConfiguration = $getStockItemConfig;
        $this->getStockItemData = $getStockItemData;
        $this->getReservationsQuantity = $getReservationsQuantity;
    }

    /**
     * @inheritdoc
     */
    public function execute(string $sku, int $stockId): float
    {
        $stockItemData = $this->getStockItemData->execute($sku, $stockId);
        $stockItemConfig = $this->getStockItemConfiguration->execute($sku, $stockId);

        return $stockItemData[GetStockItemDataInterface::QUANTITY]
            + $this->getReservationsQuantity->execute($sku, $stockId)
            - $stockItemConfig->getMinQty();
    }
}
