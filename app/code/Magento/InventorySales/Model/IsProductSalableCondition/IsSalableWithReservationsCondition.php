<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Model\IsProductSalableCondition;

use Magento\InventoryCatalog\Model\GetProductTypesBySkusInterface;
use Magento\InventoryConfiguration\Model\IsSourceItemsAllowedForProductType;
use Magento\InventoryConfigurationApi\Api\Data\StockItemConfigurationInterface;
use Magento\InventoryReservations\Model\GetReservationsQuantityInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\IsProductSalableInterface;
use Magento\InventorySales\Model\GetStockItemDataInterface;
use Magento\InventoryConfigurationApi\Api\GetStockItemConfigurationInterface;
use Magento\InventorySalesApi\Api\StockResolverInterface;

/**
 * @inheritdoc
 */
class IsSalableWithReservationsCondition implements IsProductSalableInterface
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
     * @var GetStockItemConfigurationInterface
     */
    private $getStockItemConfiguration;

    /**
     * @var IsSourceItemsAllowedForProductType
     */
    private $isSourceItemsAllowedForProductType;

    /**
     * @var GetProductTypesBySkusInterface
     */
    private $getProductTypesBySkus;

    /**
     * @var StockResolverInterface
     */
    private $stockResolver;

    /**
     * @param GetStockItemDataInterface $getStockItemData
     * @param GetReservationsQuantityInterface $getReservationsQuantity
     * @param GetStockItemConfigurationInterface $getStockItemConfiguration
     * @param IsSourceItemsAllowedForProductType $isSourceItemsAllowedForProductType
     * @param GetProductTypesBySkusInterface $getProductTypesBySkus
     * @param StockResolverInterface $stockResolver
     */
    public function __construct(
        GetStockItemDataInterface $getStockItemData,
        GetReservationsQuantityInterface $getReservationsQuantity,
        GetStockItemConfigurationInterface $getStockItemConfiguration,
        IsSourceItemsAllowedForProductType $isSourceItemsAllowedForProductType,
        GetProductTypesBySkusInterface $getProductTypesBySkus,
        StockResolverInterface $stockResolver
    ) {
        $this->getStockItemData = $getStockItemData;
        $this->getReservationsQuantity = $getReservationsQuantity;
        $this->getStockItemConfiguration = $getStockItemConfiguration;
        $this->isSourceItemsAllowedForProductType = $isSourceItemsAllowedForProductType;
        $this->getProductTypesBySkus = $getProductTypesBySkus;
        $this->stockResolver = $stockResolver;
    }

    /**
     * @inheritdoc
     */
    public function execute(string $sku, SalesChannelInterface $salesChannel): bool
    {
        $stockId = (int)$this->stockResolver->get($salesChannel->getType(), $salesChannel->getCode())->getStockId();
        $stockItemData = $this->getStockItemData->execute($sku, $stockId);
        if (null === $stockItemData) {
            // Sku is not assigned to Stock
            return false;
        }

        $productType = $this->getProductTypesBySkus->execute([$sku])[$sku];
        if (false === $this->isSourceItemsAllowedForProductType->execute($productType)) {
            return (bool)$stockItemData[GetStockItemDataInterface::IS_SALABLE];
        }

        /** @var StockItemConfigurationInterface $stockItemConfiguration */
        $stockItemConfiguration = $this->getStockItemConfiguration->execute($sku, $stockId);
        $qtyWithReservation = $stockItemData[GetStockItemDataInterface::QUANTITY] +
            $this->getReservationsQuantity->execute($sku, $stockId);
        return (bool)$stockItemData[GetStockItemDataInterface::IS_SALABLE] &&
            $qtyWithReservation > $stockItemConfiguration->getMinQty();
    }
}
