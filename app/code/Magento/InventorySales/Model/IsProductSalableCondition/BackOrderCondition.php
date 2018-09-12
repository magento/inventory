<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Model\IsProductSalableCondition;

use Magento\InventoryConfigurationApi\Api\Data\SourceItemConfigurationInterface;
use Magento\InventoryConfigurationApi\Api\Data\StockItemConfigurationInterface;
use Magento\InventoryConfigurationApi\Api\GetSourceConfigurationInterface;
use Magento\InventoryConfigurationApi\Api\GetStockConfigurationInterface;
use Magento\InventorySales\Model\ResourceModel\GetSourceCodesBySkuAndStockId;
use Magento\InventorySalesApi\Api\IsProductSalableInterface;

/**
 * @inheritdoc
 */
class BackOrderCondition implements IsProductSalableInterface
{
    /**
     * @var GetStockConfigurationInterface
     */
    private $getStockConfiguration;

    /**
     * @var GetSourceConfigurationInterface
     */
    private $getSourceConfiguration;

    /**
     * @var GetSourceCodesBySkuAndStockId
     */
    private $getSourceCodesBySkuAndStockId;

    /**
     * @param GetStockConfigurationInterface $getStockConfiguration
     * @param GetSourceConfigurationInterface $getSourceConfiguration
     * @param GetSourceCodesBySkuAndStockId $getSourceCodesBySkuAndStockId
     */
    public function __construct(
        GetStockConfigurationInterface $getStockConfiguration,
        GetSourceConfigurationInterface $getSourceConfiguration,
        GetSourceCodesBySkuAndStockId $getSourceCodesBySkuAndStockId
    ) {
        $this->getStockConfiguration = $getStockConfiguration;
        $this->getSourceConfiguration = $getSourceConfiguration;
        $this->getSourceCodesBySkuAndStockId = $getSourceCodesBySkuAndStockId;
    }

    /**
     * @inheritdoc
     */
    public function execute(string $sku, int $stockId): bool
    {
        $stockItemConfiguration = $this->getStockConfiguration->forStockItem($sku, $stockId);
        $stockConfiguration = $this->getStockConfiguration->forStock($stockId);
        $globalConfiguration = $this->getStockConfiguration->forGlobal();
        $minQty = $this->getMinQty($globalConfiguration, $stockConfiguration, $stockItemConfiguration);
        $globalSourceConfiguration = $this->getSourceConfiguration->forGlobal();
        $globalBackOrders = $globalSourceConfiguration->getBackorders();
        $result = $globalBackOrders !== SourceItemConfigurationInterface::BACKORDERS_NO && $minQty >= 0;
        $sourceCodes = $this->getSourceCodesBySkuAndStockId->execute($sku, $stockId);
        foreach ($sourceCodes as $sourceCode) {
            $backOrders = $this->getBackorders($sku, $sourceCode, $globalSourceConfiguration);
            if ($backOrders !== SourceItemConfigurationInterface::BACKORDERS_NO && $minQty >= 0) {
                $result = true;
                break;
            }
        }

        return $result;
    }

    /**
     * @param string $sku
     * @param string $sourceCode
     * @param SourceItemConfigurationInterface $globalConfiguration
     * @return int
     */
    private function getBackorders(
        string $sku,
        string $sourceCode,
        SourceItemConfigurationInterface $globalConfiguration
    ): int {
        $sourceItemConfiguration = $this->getSourceConfiguration->forSourceItem($sku, $sourceCode);
        $sourceConfiguration = $this->getSourceConfiguration->forSource($sourceCode);

        $defaultValue = $sourceConfiguration->getBackorders() !== null
            ? $sourceConfiguration->getBackorders()
            : $globalConfiguration->getBackorders();

        return $sourceItemConfiguration->getBackorders() !== null
            ? $sourceItemConfiguration->getBackorders()
            : $defaultValue;
    }

    /**
     * @param StockItemConfigurationInterface $globalConfiguration
     * @param StockItemConfigurationInterface $stockConfiguration
     * @param StockItemConfigurationInterface $stockItemConfiguration
     * @return float|null
     */
    private function getMinQty(
        StockItemConfigurationInterface $globalConfiguration,
        StockItemConfigurationInterface $stockConfiguration,
        StockItemConfigurationInterface $stockItemConfiguration
    ) {
        $defaultValue = $stockConfiguration->getMinQty() !== null
            ? $stockConfiguration->getMinQty()
            : $globalConfiguration->getMinQty();

        return $stockItemConfiguration->getMinQty() !== null ? $stockItemConfiguration->getMinQty() : $defaultValue;
    }
}
