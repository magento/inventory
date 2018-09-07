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
use Magento\InventoryConfigurationApi\Api\Data\SourceItemConfigurationInterface;
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
    private $getStockItemConfiguration;

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
     * @param GetStockConfigurationInterface $getStockItemConfig
     * @param GetProductSalableQtyInterface $getProductSalableQty
     * @param IsSourceItemManagementAllowedForProductTypeInterface $isSourceItemManagementAllowedForProductType
     * @param CollectionFactory $sourceItemCollectionFactory
     * @param GetSourceConfigurationInterface $getSourceConfiguration
     */
    public function __construct(
        StockByWebsiteIdResolverInterface $stockByWebsiteId,
        GetStockConfigurationInterface $getStockItemConfig,
        GetProductSalableQtyInterface $getProductSalableQty,
        IsSourceItemManagementAllowedForProductTypeInterface $isSourceItemManagementAllowedForProductType,
        CollectionFactory $sourceItemCollectionFactory,
        GetSourceConfigurationInterface $getSourceConfiguration
    ) {
        $this->getStockItemConfiguration = $getStockItemConfig;
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
        $stockItemConfig = $this->getStockItemConfiguration->forStockItem($sku, $stockId);
        //todo; Temporal solution. Should be reworked.
        $sourceItemCollection = $this->sourceItemCollectionFactory->create();
        $sourceItemCollection->addFieldToSelect('source_code');
        $sourceItemCollection->addFieldToFilter('sku', ['eq' => $sku]);
        $sourceItemCollection->getSelect()->join(
            ['link' => $sourceItemCollection->getTable('inventory_source_stock_link')],
            'main_table.source_code = link.source_code',
            []
        )->where('link.stock_id = ?', $stockId);

        foreach ($sourceItemCollection as $sourceItem) {
            $sourceItemConfiguration = $this->getSourceConfiguration->forSourceItem($sku, $sourceItem->getSourceCode());
            if (($sourceItemConfiguration->getBackorders() === SourceItemConfigurationInterface::BACKORDERS_NO
                    || $sourceItemConfiguration->getBackorders() !== SourceItemConfigurationInterface::BACKORDERS_NO
                    && $stockItemConfig->getMinQty() < 0)
                && $this->getProductSalableQty->execute($sku, $stockId) <= $stockItemConfig->getStockThresholdQty()) {
                return true;
            }
        }

        return false;
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
}
