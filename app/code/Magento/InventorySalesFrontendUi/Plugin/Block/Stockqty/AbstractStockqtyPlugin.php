<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySalesFrontendUi\Plugin\Block\Stockqty;

use Magento\CatalogInventory\Block\Stockqty\AbstractStockqty;
use Magento\InventoryConfigurationApi\Api\Data\SourceItemConfigurationInterface;
use Magento\InventoryConfigurationApi\Api\GetInventoryConfigurationInterface;
use Magento\InventoryConfigurationApi\Model\IsSourceItemManagementAllowedForProductTypeInterface;
use Magento\InventorySalesApi\Api\GetProductSalableQtyInterface;
use Magento\InventorySalesApi\Model\StockByWebsiteIdResolverInterface;
use Magento\Framework\Exception\LocalizedException;

class AbstractStockqtyPlugin
{
    /**
     * @var StockByWebsiteIdResolverInterface
     */
    private $stockByWebsiteId;

    /**
     * @var GetInventoryConfigurationInterface
     */
    private $getInventoryConfiguration;

    /**
     * @var IsSourceItemManagementAllowedForProductTypeInterface
     */
    private $isSourceItemManagementAllowedForProductType;

    /**
     * @param StockByWebsiteIdResolverInterface $stockByWebsiteId
     * @param GetInventoryConfigurationInterface $getInventoryConfiguration
     * @param GetProductSalableQtyInterface $getProductSalableQty
     * @param IsSourceItemManagementAllowedForProductTypeInterface $isSourceItemManagementAllowedForProductType
     */
    public function __construct(
        StockByWebsiteIdResolverInterface $stockByWebsiteId,
        GetInventoryConfigurationInterface $getInventoryConfiguration,
        GetProductSalableQtyInterface $getProductSalableQty,
        IsSourceItemManagementAllowedForProductTypeInterface $isSourceItemManagementAllowedForProductType
    ) {
        $this->stockByWebsiteId = $stockByWebsiteId;
        $this->getInventoryConfiguration = $getInventoryConfiguration;
        $this->getProductSalableQty = $getProductSalableQty;
        $this->isSourceItemManagementAllowedForProductType = $isSourceItemManagementAllowedForProductType;
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
        $backorders = $this->getInventoryConfiguration->getBackorders($sku, $stockId);
        $minQty = $this->getInventoryConfiguration->getMinQty($sku, $stockId);
        $stockThresholdQty = $this->getInventoryConfiguration->getStockThresholdQty($sku, $stockId);


        return ($backorders === SourceItemConfigurationInterface::BACKORDERS_NO
            || $backorders !== SourceItemConfigurationInterface::BACKORDERS_NO
            && $minQty < 0)
            && $this->getProductSalableQty->execute($sku, $stockId) <= $stockThresholdQty;
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
