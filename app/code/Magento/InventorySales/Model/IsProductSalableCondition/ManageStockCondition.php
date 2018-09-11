<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Model\IsProductSalableCondition;

use Magento\InventoryConfigurationApi\Api\GetStockConfigurationInterface;
use Magento\InventorySalesApi\Api\IsProductSalableInterface;

/**
 * @inheritdoc
 */
class ManageStockCondition implements IsProductSalableInterface
{
    /**
     * @var GetStockConfigurationInterface
     */
    private $getStockConfiguration;

    /**
     * @param GetStockConfigurationInterface $getStockItemConfiguration
     */
    public function __construct(
        GetStockConfigurationInterface $getStockItemConfiguration
    ) {
        $this->getStockConfiguration = $getStockItemConfiguration;
    }

    /**
     * @inheritdoc
     */
    public function execute(string $sku, int $stockId): bool
    {
        $stockItemConfiguration = $this->getStockConfiguration->forStockItem($sku, $stockId);
        $stockConfiguration = $this->getStockConfiguration->forStock($stockId);
        $globalConfiguration = $this->getStockConfiguration->forGlobal();
        $defaultValue = $stockConfiguration->isManageStock() !== null
            ? $stockConfiguration->isManageStock()
            : $globalConfiguration->isManageStock();
        $manageStock = $stockItemConfiguration->isManageStock() !== null
            ? $stockItemConfiguration->isManageStock()
            : $defaultValue;

        return !$manageStock;
    }
}
