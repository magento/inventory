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
     * Is salable quantity available for displaying.
     *
     * @param float $productSalableQty
     * @return bool
     */
    public function isSalableQtyAvailableForDisplaying(float $productSalableQty): bool
    {
        return ($this->stockItemConfig->getBackorders() === StockItemConfigurationInterface::BACKORDERS_NO
                || $this->stockItemConfig->getBackorders() !== StockItemConfigurationInterface::BACKORDERS_NO
                && $this->stockItemConfig->getMinQty() < 0)
            && bccomp((string)$productSalableQty, (string)$this->stockItemConfig->getStockThresholdQty(), 12) !== 1
            && $productSalableQty > 0;
    }
}
