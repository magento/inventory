<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Model\IsProductSalableCondition;

use Magento\InventoryConfigurationApi\Model\IsSourceItemManagementAllowedForSkuInterface;
use Magento\InventorySales\Model\ResourceModel\GetIsAnySourceItemInStock;
use Magento\InventorySalesApi\Api\IsProductSalableInterface;

/**
 * Check if product has source items with the in stock status
 */
class IsAnySourceItemInStockCondition implements IsProductSalableInterface
{
    /**
     * @var IsSourceItemManagementAllowedForSkuInterface
     */
    private $isSourceItemManagementAllowedForSku;

    /**
     * @var ManageStockCondition
     */
    private $manageStockCondition;

    /**
     * @var GetIsAnySourceItemInStock
     */
    private $getIsAnySourceItemInStock;

    /**
     * @param IsSourceItemManagementAllowedForSkuInterface $isSourceItemManagementAllowedForSku
     * @param ManageStockCondition $manageStockCondition
     * @param GetIsAnySourceItemInStock $getIsAnySourceItemInStock
     */
    public function __construct(
        IsSourceItemManagementAllowedForSkuInterface $isSourceItemManagementAllowedForSku,
        ManageStockCondition $manageStockCondition,
        GetIsAnySourceItemInStock $getIsAnySourceItemInStock
    ) {
        $this->isSourceItemManagementAllowedForSku = $isSourceItemManagementAllowedForSku;
        $this->manageStockCondition = $manageStockCondition;
        $this->getIsAnySourceItemInStock = $getIsAnySourceItemInStock;
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

        return $this->getIsAnySourceItemInStock->execute($sku, $stockId);
    }
}
