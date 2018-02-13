<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Inventory\Model;

use Magento\InventoryApi\Api\IsProductSalableInterface;
use Magento\InventoryConfiguration\Model\StockItemConfigurationInterface;

/**
 * @inheritdoc
 */
class IsProductSalable implements IsProductSalableInterface
{
    /**
     * @var GetStockItemDataInterface
     */
    private $getStockItemData;

    /**
     * @var GetReservationsQuantityInterface
     */
    private $getReservationsQuantity;

    /**
     * @var StockItemConfigurationInterface
     */
    private $stockConfiguration;

    /**
     * IsProductSalable constructor.
     * @param GetStockItemDataInterface $getStockItemData
     * @param GetReservationsQuantityInterface $getReservationsQuantity
     * @param StockItemConfigurationInterface $stockConfiguration
     */
    public function __construct(
        GetStockItemDataInterface $getStockItemData,
        GetReservationsQuantityInterface $getReservationsQuantity,
        StockItemConfigurationInterface $stockConfiguration
    ) {
        $this->getReservationsQuantity = $getReservationsQuantity;
        $this->stockConfiguration = $stockConfiguration;
        $this->getStockItemData = $getStockItemData;
    }

    /**
     * @inheritdoc
     */
    public function execute(string $sku, int $stockId): bool
    {
        $stockItemData = $this->getStockItemData->execute($sku, $stockId);
        if (null === $stockItemData) {
            return false;
        }

        $isSalable = (bool)$stockItemData['is_salable'];
        $qtyWithReservation = $stockItemData['quantity'] + $this->getReservationsQuantity->execute($sku, $stockId);

        return $this->stockConfiguration->execute($sku, $stockId, $qtyWithReservation, $isSalable);
    }
}
