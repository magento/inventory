<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfiguration\Model\ConfigurationOptions;

use Magento\InventoryConfigurationApi\Api\SetManageStockStatusConfigurationValueInterface;
use Magento\InventoryConfigurationApi\Api\StockItemConfigurationInterface;
use Magento\InventoryConfiguration\Model\ResourceModel\SetConfigurationValue;

class SetManageStockStatusConfigurationValue implements SetManageStockStatusConfigurationValueInterface
{
    /**
     * @var SetConfigurationValue
     */
    private $setConfigurationValue;

    /**
     * @param SetConfigurationValue $setConfigurationValue
     */
    public function __construct(
        SetConfigurationValue $setConfigurationValue
    ) {
        $this->setConfigurationValue = $setConfigurationValue;
    }

    /**
     * @param string $sku
     * @param int $stockId
     * @param int|null $manageStock
     * @return void
     */
    public function forStockItem(string $sku, int $stockId, ?int $manageStock): void
    {
        $this->setConfigurationValue->execute(
            StockItemConfigurationInterface::MANAGE_STOCK,
            (string)$manageStock,
            $stockId,
            null,
            $sku
        );
    }

    /**
     * @param int $stockId
     * @param int|null $manageStock
     * @return void
     */
    public function forStock(int $stockId, ?int $manageStock): void
    {
        $this->setConfigurationValue->execute(
            StockItemConfigurationInterface::MANAGE_STOCK,
            (string)$manageStock,
            $stockId
        );
    }

    /**
     * @param int|null $manageStock
     * @return void
     */
    public function forGlobal(?int $manageStock): void
    {
        $this->setConfigurationValue->execute(
            StockItemConfigurationInterface::MANAGE_STOCK,
            (string)$manageStock
        );
    }
}
