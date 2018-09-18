<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalogConfiguration\Plugin\InventoryConfigurationApi;

use Magento\InventoryConfigurationApi\Api\Data\StockItemConfigurationInterface;
use Magento\InventoryConfigurationApi\Api\SaveStockConfigurationInterface;

class SetDataToLegacyCatalogInventoryAtStockItemConfigurationSavePlugin
{
    /**
     * @var SaveStockConfigurationInterface
     */
    private $saveStockConfiguration;

    /**
     * @param SaveStockConfigurationInterface $saveStockConfiguration
     */
    public function __construct(
        SaveStockConfigurationInterface $saveStockConfiguration
    ) {
        $this->saveStockConfiguration = $saveStockConfiguration;
    }

    /**
     * @param SaveStockConfigurationInterface $subject
     * @param callable $proceed
     * @param string $sku
     * @param int $stockId
     * @param StockItemConfigurationInterface $stockItemConfiguration
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundForStockItem(
        SaveStockConfigurationInterface $subject,
        callable $proceed,
        string $sku,
        int $stockId,
        StockItemConfigurationInterface $stockItemConfiguration
    ): void {
        $this->saveStockConfiguration->forStockItem(
            $sku,
            $stockId,
            $stockItemConfiguration
        );
    }
}
