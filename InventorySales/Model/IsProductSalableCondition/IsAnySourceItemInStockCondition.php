<?php
/**
 * Copyright 2019 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Model\IsProductSalableCondition;

use Magento\InventoryConfigurationApi\Model\IsSourceItemManagementAllowedForSkuInterface;
use Magento\InventorySalesApi\Api\IsProductSalableInterface;
use Magento\InventorySalesApi\Model\GetProductAvailableQtyInterface;

/**
 * Check if product has source items with the in stock status
 */
class IsAnySourceItemInStockCondition implements IsProductSalableInterface
{
    /**
     * @param IsSourceItemManagementAllowedForSkuInterface $isSourceItemManagementAllowedForSku
     * @param ManageStockCondition $manageStockCondition
     * @param GetProductAvailableQtyInterface $getProductAvailableQty
     */
    public function __construct(
        private readonly IsSourceItemManagementAllowedForSkuInterface $isSourceItemManagementAllowedForSku,
        private readonly ManageStockCondition $manageStockCondition,
        private readonly GetProductAvailableQtyInterface $getProductAvailableQty
    ) {
    }

    /**
     * @inheritdoc
     */
    public function execute(string $sku, int $stockId): bool
    {
        // TODO Must be removed once MSI-2131 is complete.
        if ($this->manageStockCondition->execute($sku, $stockId)) {
            return true;
        }

        if (!$this->isSourceItemManagementAllowedForSku->execute($sku)) {
            return true;
        }

        return $this->getProductAvailableQty->execute($sku, $stockId) !== null;
    }
}
