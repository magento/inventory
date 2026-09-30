<?php
/**
 * Copyright 2020 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalogFrontendUi\Model;

use Magento\Framework\App\ObjectManager;
use Magento\InventoryConfigurationApi\Api\GetStockItemConfigurationInterface;
use Magento\InventoryConfigurationApi\Exception\SkuIsNotAssignedToStockException;
use Magento\InventorySalesApi\Api\GetProductSalableQtyInterface;

/**
 * Model for getting product qty left.
 */
class GetProductQtyLeft
{
    private GetProductSalableQtyInterface $getProductSalableQty;

    private IsSalableQtyThresholdReached $isSalableQtyThresholdReached;

    private GetStockItemConfigurationInterface $getStockItemConfig;

    /**
     * @param IsSalableQtyAvailableForDisplaying $qtyLeftChecker [Deprecated]
     * @param GetProductSalableQtyInterface $getProductSalableQty
     * @param IsSalableQtyThresholdReached|null $isSalableQtyThresholdReached
     * @param GetStockItemConfigurationInterface|null $getStockItemConfig
     */
    public function __construct(
        IsSalableQtyAvailableForDisplaying $qtyLeftChecker,
        GetProductSalableQtyInterface $getProductSalableQty,
        ?IsSalableQtyThresholdReached $isSalableQtyThresholdReached = null,
        ?GetStockItemConfigurationInterface $getStockItemConfig = null
    ) {
        $this->getProductSalableQty = $getProductSalableQty;
        $this->isSalableQtyThresholdReached = $isSalableQtyThresholdReached
            ?? ObjectManager::getInstance()->get(IsSalableQtyThresholdReached::class);
        $this->getStockItemConfig = $getStockItemConfig ?? ObjectManager::getInstance()->get(GetStockItemConfigurationInterface::class);
    }

    /**
     * Get salable qty if it is possible.
     *
     * @param string $productSku
     * @param int $stockId
     * @return float
     * @throws SkuIsNotAssignedToStockException
     */
    public function execute(string $productSku, int $stockId): float
    {
        $productSalableQty = $this->getProductSalableQty->execute($productSku, $stockId);
        $stockItemConfig = $this->getStockItemConfig->execute($productSku, $stockId);

        return $this->isSalableQtyThresholdReached->execute($productSalableQty, $stockItemConfig)
            ? $productSalableQty
            : 0;
    }
}
