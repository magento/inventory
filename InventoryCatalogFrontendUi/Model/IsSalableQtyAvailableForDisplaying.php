<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalogFrontendUi\Model;

use Magento\InventoryConfigurationApi\Api\Data\StockItemConfigurationInterface;

/**
 * Check if it is necessary to show qty left.
 *
 * @deprecated
 */
class IsSalableQtyAvailableForDisplaying
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
    public function execute(float $productSalableQty): bool
    {
        return ($this->stockItemConfig->getBackorders() === StockItemConfigurationInterface::BACKORDERS_NO
                || ($this->stockItemConfig->getBackorders() !== StockItemConfigurationInterface::BACKORDERS_NO
                && $this->stockItemConfig->getMinQty() < 0))
            && $productSalableQty <= $this->stockItemConfig->getStockThresholdQty()
            && $productSalableQty > 0;
    }
}
