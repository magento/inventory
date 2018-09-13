<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySalesFrontendUi\Plugin\Block\Stockqty;

use Magento\CatalogInventory\Block\Stockqty\AbstractStockqty;
use Magento\Framework\Exception\LocalizedException;
use Magento\InventoryConfigurationApi\Api\Data\SourceItemConfigurationInterface;
use Magento\InventoryConfigurationApi\Api\Data\StockItemConfigurationInterface;
use Magento\InventoryConfigurationApi\Api\GetSourceConfigurationInterface;
use Magento\InventoryConfigurationApi\Api\GetStockConfigurationInterface;
use Magento\InventoryConfigurationApi\Model\IsSourceItemManagementAllowedForProductTypeInterface;
use Magento\InventorySales\Model\ResourceModel\GetSourceCodesBySkuAndStockId;
use Magento\InventorySalesApi\Api\GetProductSalableQtyInterface;
use Magento\InventorySalesApi\Model\StockByWebsiteIdResolverInterface;

class AbstractStockqtyPlugin
{
    /**
     * @var GetStockConfigurationInterface
     */
    private $getStockConfiguration;

    /**
     * @var StockByWebsiteIdResolverInterface
     */
    private $stockByWebsiteId;

    /**
     * @var GetProductSalableQtyInterface
     */
    private $getProductSalableQty;

    /**
     * @var IsSourceItemManagementAllowedForProductTypeInterface
     */
    private $isSourceItemManagementAllowedForProductType;

    /**
     * @var GetSourceConfigurationInterface
     */
    private $getSourceConfiguration;

    /**
     * @var GetSourceCodesBySkuAndStockId
     */
    private $getSourceCodesBySkuAndStockId;

    /**
     * @param StockByWebsiteIdResolverInterface $stockByWebsiteId
     * @param GetStockConfigurationInterface $getStockConfiguration
     * @param GetProductSalableQtyInterface $getProductSalableQty
     * @param IsSourceItemManagementAllowedForProductTypeInterface $isSourceItemManagementAllowedForProductType
     * @param GetSourceConfigurationInterface $getSourceConfiguration
     * @param GetSourceCodesBySkuAndStockId $getSourceCodesBySkuAndStockId
     */
    public function __construct(
        StockByWebsiteIdResolverInterface $stockByWebsiteId,
        GetStockConfigurationInterface $getStockConfiguration,
        GetProductSalableQtyInterface $getProductSalableQty,
        IsSourceItemManagementAllowedForProductTypeInterface $isSourceItemManagementAllowedForProductType,
        GetSourceConfigurationInterface $getSourceConfiguration,
        GetSourceCodesBySkuAndStockId $getSourceCodesBySkuAndStockId
    ) {
        $this->getStockConfiguration = $getStockConfiguration;
        $this->stockByWebsiteId = $stockByWebsiteId;
        $this->getProductSalableQty = $getProductSalableQty;
        $this->isSourceItemManagementAllowedForProductType = $isSourceItemManagementAllowedForProductType;
        $this->getSourceConfiguration = $getSourceConfiguration;
        $this->getSourceCodesBySkuAndStockId = $getSourceCodesBySkuAndStockId;
    }

    /**
     * @param AbstractStockqty $subject
     * @param callable $proceed
     * @return bool
     * @throws LocalizedException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundIsMsgVisible(AbstractStockqty $subject, callable $proceed): bool
    {
        $productType = $subject->getProduct()->getTypeId();
        if (!$this->isSourceItemManagementAllowedForProductType->execute($productType)) {
            return false;
        }

        $sku = (string)$subject->getProduct()->getSku();
        $websiteId = (int)$subject->getProduct()->getStore()->getWebsiteId();
        $stockId = (int)$this->stockByWebsiteId->execute($websiteId)->getStockId();

        return $this->isMsgVisible($sku, $stockId);
    }

    /**
     * @param AbstractStockqty $subject
     * @param callable $proceed
     * @return float
     * @throws LocalizedException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundGetStockQtyLeft(AbstractStockqty $subject, callable $proceed): float
    {
        $sku = $subject->getProduct()->getSku();
        $websiteId = (int)$subject->getProduct()->getStore()->getWebsiteId();
        $stockId = (int)$this->stockByWebsiteId->execute($websiteId)->getStockId();
        return $this->getProductSalableQty->execute($sku, $stockId);
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
     * @return float
     */
    private function getMinQty(
        StockItemConfigurationInterface $globalConfiguration,
        StockItemConfigurationInterface $stockConfiguration,
        StockItemConfigurationInterface $stockItemConfiguration
    ): float {
        $defaultValue = $stockConfiguration->getMinQty() !== null
            ? $stockConfiguration->getMinQty()
            : $globalConfiguration->getMinQty();

        return $stockItemConfiguration->getMinQty() !== null ? $stockItemConfiguration->getMinQty() : $defaultValue;
    }

    /**
     * @param StockItemConfigurationInterface $globalConfiguration
     * @param StockItemConfigurationInterface $stockConfiguration
     * @param StockItemConfigurationInterface $stockItemConfiguration
     * @return float
     */
    private function getThresholdQty(
        StockItemConfigurationInterface $globalConfiguration,
        StockItemConfigurationInterface $stockConfiguration,
        StockItemConfigurationInterface $stockItemConfiguration
    ): float {
        $defaultValue = $stockConfiguration->getStockThresholdQty() !== null
            ? $stockConfiguration->getStockThresholdQty()
            : $globalConfiguration->getStockThresholdQty();

        return $stockItemConfiguration->getStockThresholdQty() !== null
            ? $stockItemConfiguration->getStockThresholdQty()
            : $defaultValue;
    }

    /**
     * @param string $sku
     * @param int $stockId
     * @return bool
     * @throws LocalizedException
     * @throws \Magento\Framework\Exception\InputException
     */
    private function isMsgVisible(string $sku, int $stockId): bool
    {
        $stockItemConfiguration = $this->getStockConfiguration->forStockItem($sku, $stockId);
        $stockConfiguration = $this->getStockConfiguration->forStock($stockId);
        $globalConfiguration = $this->getStockConfiguration->forGlobal();
        $minQty = $this->getMinQty($globalConfiguration, $stockConfiguration, $stockItemConfiguration);
        $thresholdQty = $this->getThresholdQty($globalConfiguration, $stockConfiguration, $stockItemConfiguration);
        $globalSourceConfiguration = $this->getSourceConfiguration->forGlobal();
        $globalBackOrders = $globalSourceConfiguration->getBackorders();
        $result = ($globalBackOrders === SourceItemConfigurationInterface::BACKORDERS_NO
                || $globalBackOrders !== SourceItemConfigurationInterface::BACKORDERS_NO
                && $minQty < 0) && $this->getProductSalableQty->execute($sku, $stockId) <= $thresholdQty;
        $sourceCodes = $this->getSourceCodesBySkuAndStockId->execute($sku, $stockId);
        foreach ($sourceCodes as $sourceCode) {
            $backorders = $this->getBackorders($sku, $sourceCode, $globalSourceConfiguration);
            if (($backorders === SourceItemConfigurationInterface::BACKORDERS_NO
                    || $backorders !== SourceItemConfigurationInterface::BACKORDERS_NO
                    && $minQty < 0)
                && $this->getProductSalableQty->execute($sku, $stockId) <= $thresholdQty) {
                $result = true;
                break;
            }
        }
        return $result;
    }
}
