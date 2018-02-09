<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Inventory\Model;

use Magento\CatalogInventory\Api\StockItemCriteriaInterfaceFactory;
use Magento\InventoryApi\Api\IsProductSalableInterface;
use Magento\InventoryConfiguration\Model\StockConfigurationInterface;

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
     * @var StockConfigurationInterface
     */
    private $stockConfiguration;

    /**
     * IsProductSalable constructor.
     * @param GetStockItemDataInterface $getStockItemData
     * @param GetReservationsQuantityInterface $getReservationsQuantity
     * @param StockConfigurationInterface $stockConfiguration
     */
    public function __construct(
        GetStockItemDataInterface $getStockItemData,
        GetReservationsQuantityInterface $getReservationsQuantity,
        StockConfigurationInterface $stockConfiguration
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

        return $this->stockConfiguration->validate($sku, $stockId, $qtyWithReservation, $isSalable);
    }
}
