<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalogFrontendUi\Model;

use Magento\InventoryConfigurationApi\Api\Data\StockItemConfigurationInterface;

/**
 * Qty left checker.
 */
class QtyLeftChecker
{
    /**
     * @var StockItemConfigurationInterface
     */
    private $stockItemConfig;

    /**
     * @param StockItemConfigurationInterface $stockItemConfiguration
     */
    public function __construct(
        StockItemConfigurationInterface $stockItemConfiguration
    ) {
        $this->stockItemConfig = $stockItemConfiguration;
    }

    /**
     * Use qty left for viewing.
     *
     * @param float $productSalableQty
     * @return bool
     */
    public function useQtyForViewing(float $productSalableQty): bool
    {
        return ($this->stockItemConfig->getBackorders() === StockItemConfigurationInterface::BACKORDERS_NO
            || $this->stockItemConfig->getBackorders() !== StockItemConfigurationInterface::BACKORDERS_NO
            && $this->stockItemConfig->getMinQty() < 0)
            && $productSalableQty <= $this->stockItemConfig->getStockThresholdQty()
            && $productSalableQty > 0;
    }
}
