<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySalesFrontendUi\Plugin\Block\Stockqty;

use Magento\CatalogInventory\Block\Stockqty\AbstractStockqty;
use Magento\Framework\Exception\LocalizedException;
use Magento\Inventory\Model\ResourceModel\SourceItem\CollectionFactory;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryConfigurationApi\Api\Data\SourceItemConfigurationInterface;
use Magento\InventoryConfigurationApi\Api\Data\StockItemConfigurationInterface;
use Magento\InventoryConfigurationApi\Api\GetSourceConfigurationInterface;
use Magento\InventoryConfigurationApi\Api\GetStockConfigurationInterface;
use Magento\InventoryConfigurationApi\Model\IsSourceItemManagementAllowedForProductTypeInterface;
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
     * @var CollectionFactory
     */
    private $sourceItemCollectionFactory;

    /**
     * @var GetSourceConfigurationInterface
     */
    private $getSourceConfiguration;

    /**
     * @param StockByWebsiteIdResolverInterface $stockByWebsiteId
     * @param GetStockConfigurationInterface $getStockConfiguration
     * @param GetProductSalableQtyInterface $getProductSalableQty
     * @param IsSourceItemManagementAllowedForProductTypeInterface $isSourceItemManagementAllowedForProductType
     * @param CollectionFactory $sourceItemCollectionFactory
     * @param GetSourceConfigurationInterface $getSourceConfiguration
     */
    public function __construct(
        StockByWebsiteIdResolverInterface $stockByWebsiteId,
        GetStockConfigurationInterface $getStockConfiguration,
        GetProductSalableQtyInterface $getProductSalableQty,
        IsSourceItemManagementAllowedForProductTypeInterface $isSourceItemManagementAllowedForProductType,
        CollectionFactory $sourceItemCollectionFactory,
        GetSourceConfigurationInterface $getSourceConfiguration
    ) {
        $this->getStockConfiguration = $getStockConfiguration;
        $this->stockByWebsiteId = $stockByWebsiteId;
        $this->getProductSalableQty = $getProductSalableQty;
        $this->isSourceItemManagementAllowedForProductType = $isSourceItemManagementAllowedForProductType;
        $this->sourceItemCollectionFactory = $sourceItemCollectionFactory;
        $this->getSourceConfiguration = $getSourceConfiguration;
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

        $sku = $subject->getProduct()->getSku();
        $websiteId = (int)$subject->getProduct()->getStore()->getWebsiteId();
        $stockId = (int)$this->stockByWebsiteId->execute($websiteId)->getStockId();
        $stockItemConfiguration = $this->getStockConfiguration->forStockItem($sku, $stockId);
        $stockConfiguration = $this->getStockConfiguration->forStock($stockId);
        $globalConfiguration = $this->getStockConfiguration->forGlobal();
        $minQty = $this->getMinQty($globalConfiguration, $stockConfiguration, $stockItemConfiguration);
        $thresholdQty = $this->getThresholdQty($globalConfiguration, $stockConfiguration, $stockItemConfiguration);
        //todo; Temporal solution. Should be reworked.
        $sourceItemCollection = $this->sourceItemCollectionFactory->create();
        $sourceItemCollection->addFieldToSelect('source_code');
        $sourceItemCollection->addFieldToFilter('sku', ['eq' => $sku]);
        $sourceItemCollection->getSelect()->join(
            ['link' => $sourceItemCollection->getTable('inventory_source_stock_link')],
            'main_table.source_code = link.source_code',
            []
        )->where('link.stock_id = ?', $stockId);
        $globalSourceConfiguration = $this->getSourceConfiguration->forGlobal();
        $globalBackOrders = $globalSourceConfiguration->getBackorders();
        $result = ($globalBackOrders === SourceItemConfigurationInterface::BACKORDERS_NO
            || $globalBackOrders !== SourceItemConfigurationInterface::BACKORDERS_NO
            && $minQty < 0) && $this->getProductSalableQty->execute($sku, $stockId) <= $thresholdQty;
        foreach ($sourceItemCollection as $sourceItem) {
            $backorders = $this->getBackorders($sku, $sourceItem, $globalSourceConfiguration);
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
     * @param SourceItemInterface $sourceItem
     * @param SourceItemConfigurationInterface $globalConfiguration
     * @return int
     */
    private function getBackorders(
        string $sku,
        SourceItemInterface $sourceItem,
        SourceItemConfigurationInterface $globalConfiguration
    ): int {
        $sourceItemConfiguration = $this->getSourceConfiguration->forSourceItem($sku, $sourceItem->getSourceCode());
        $sourceConfiguration = $this->getSourceConfiguration->forSource($sourceItem->getSourceCode());

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
}
