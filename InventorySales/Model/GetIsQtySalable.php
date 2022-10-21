<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Model;

use Magento\InventoryCatalogApi\Model\GetProductTypesBySkusInterface;
use Magento\InventoryConfigurationApi\Model\IsSourceItemManagementAllowedForProductTypeInterface;
use Magento\InventorySalesApi\Model\GetIsQtySalableInterface;
use Magento\InventorySalesApi\Model\GetSalableQtyInterface;
use Magento\InventorySalesApi\Model\GetStockItemDataInterface;

/**
 * @inheritdoc
 */
class GetIsQtySalable implements GetIsQtySalableInterface
{
    /**
     * @var GetStockItemDataInterface
     */
    private $getStockItemData;

    /**
     * @var IsSourceItemManagementAllowedForProductTypeInterface
     */
    private $isSourceItemManagementAllowedForProductType;

    /**
     * @var GetProductTypesBySkusInterface
     */
    private $getProductTypesBySkus;

    /**
     * @var GetSalableQtyInterface
     */
    private $getProductQtyInStock;

    /**
     * @param GetStockItemDataInterface $getStockItemData
     * @param IsSourceItemManagementAllowedForProductTypeInterface $isSourceItemManagementAllowedForProductType
     * @param GetProductTypesBySkusInterface $getProductTypesBySkus
     * @param GetSalableQtyInterface $getProductQtyInStock
     */
    public function __construct(
        GetStockItemDataInterface $getStockItemData,
        IsSourceItemManagementAllowedForProductTypeInterface $isSourceItemManagementAllowedForProductType,
        GetProductTypesBySkusInterface $getProductTypesBySkus,
        GetSalableQtyInterface $getProductQtyInStock
    ) {
        $this->getStockItemData = $getStockItemData;
        $this->isSourceItemManagementAllowedForProductType = $isSourceItemManagementAllowedForProductType;
        $this->getProductTypesBySkus = $getProductTypesBySkus;
        $this->getProductQtyInStock = $getProductQtyInStock;
    }

    /**
     * @inheritdoc
     */
    public function execute(string $sku, int $stockId): bool
    {
        $stockItemData = $this->getStockItemData->execute($sku, $stockId);
        if (null === $stockItemData) {
            // Sku is not assigned to Stock
            return false;
        }

        $productType = $this->getProductTypesBySkus->execute([$sku])[$sku];
        if (false === $this->isSourceItemManagementAllowedForProductType->execute($productType)) {
            return (bool)$stockItemData[GetStockItemDataInterface::IS_SALABLE];
        }

        return $this->getProductQtyInStock->execute($sku, $stockId) > 0;
    }
}
