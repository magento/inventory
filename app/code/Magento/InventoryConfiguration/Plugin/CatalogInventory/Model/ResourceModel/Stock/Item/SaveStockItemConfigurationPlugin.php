<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfiguration\Plugin\CatalogInventory\Model\ResourceModel\Stock\Item;

use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Model\ResourceModel\Stock\Item as ItemResourceModel;
use Magento\InventoryCatalogApi\Api\DefaultStockProviderInterface;
use Magento\InventoryCatalogApi\Model\GetSkusByProductIdsInterface;
use Magento\InventoryConfigurationApi\Api\Data\StockItemConfigurationInterface;
use Magento\InventoryConfigurationApi\Api\GetStockConfigurationInterface;
use Magento\InventoryConfigurationApi\Api\SaveStockConfigurationInterface;

/**
 * Save stock item configuration for given product and default stock after stock item was saved successfully.
 */
class SaveStockItemConfigurationPlugin
{
    /**
     * @var GetStockConfigurationInterface
     */
    private $getStockConfiguration;

    /**
     * @var SaveStockConfigurationInterface
     */
    private $saveStockConfiguration;

    /**
     * @var GetSkusByProductIdsInterface
     */
    private $getSkusByProductIds;

    /**
     * @var DefaultStockProviderInterface
     */
    private $defaultStockProvider;

    /**
     * @param GetStockConfigurationInterface $getStockConfiguration
     * @param SaveStockConfigurationInterface $saveStockConfiguration
     * @param GetSkusByProductIdsInterface $getSkusByProductIds
     * @param DefaultStockProviderInterface $defaultStockProvider
     */
    public function __construct(
        GetStockConfigurationInterface $getStockConfiguration,
        SaveStockConfigurationInterface $saveStockConfiguration,
        GetSkusByProductIdsInterface $getSkusByProductIds,
        DefaultStockProviderInterface $defaultStockProvider
    ) {
        $this->getStockConfiguration = $getStockConfiguration;
        $this->saveStockConfiguration = $saveStockConfiguration;
        $this->getSkusByProductIds = $getSkusByProductIds;
        $this->defaultStockProvider = $defaultStockProvider;
    }

    /**
     * @param ItemResourceModel $subject
     * @param ItemResourceModel $result
     * @param StockItemInterface $stockItem
     * @return ItemResourceModel
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterSave(
        ItemResourceModel $subject,
        ItemResourceModel $result,
        StockItemInterface $stockItem
    ): ItemResourceModel {
        $productId = $stockItem->getProductId();
        $skus = $this->getSkusByProductIds->execute([$productId]);
        $productSku = $skus[$productId];

        $stockItemConfiguration = $this->getStockConfiguration->forStockItem(
            $productSku,
            $this->defaultStockProvider->getId()
        );
        if ($stockItem->getData('use_config_' . StockItemConfigurationInterface::MANAGE_STOCK)) {
            $stockItemConfiguration->setManageStock(null);
        } elseif ($stockItem->getData(StockItemConfigurationInterface::MANAGE_STOCK) !== null) {
            $stockItemConfiguration->setManageStock(
                (bool)$stockItem->getData(StockItemConfigurationInterface::MANAGE_STOCK)
            );
        }

        if ($stockItem->getData('use_config_' . StockItemConfigurationInterface::MIN_QTY)) {
            $stockItemConfiguration->setMinQty(null);
        } elseif ($stockItem->getData(StockItemConfigurationInterface::MIN_QTY) !== null) {
            $stockItemConfiguration->setMinQty(
                (float)$stockItem->getData(StockItemConfigurationInterface::MIN_QTY)
            );
        }

        if ($stockItem->getData('use_config_' . StockItemConfigurationInterface::MIN_QTY)) {
            $stockItemConfiguration->setStockThresholdQty(null);
        } elseif ($stockItem->getData(StockItemConfigurationInterface::STOCK_THRESHOLD_QTY) !== null) {
            $stockItemConfiguration->setStockThresholdQty(
                (float)$stockItem->getData(StockItemConfigurationInterface::STOCK_THRESHOLD_QTY)
            );
        }

        if ($stockItem->getData('use_config_' . StockItemConfigurationInterface::MIN_SALE_QTY)) {
            $stockItemConfiguration->setMinSaleQty(null);
        } elseif ($stockItem->getData(StockItemConfigurationInterface::MIN_SALE_QTY) !== null) {
            $stockItemConfiguration->setMinSaleQty(
                (float)$stockItem->getData(StockItemConfigurationInterface::MIN_SALE_QTY)
            );
        }

        if ($stockItem->getData('use_config_' . StockItemConfigurationInterface::MAX_SALE_QTY)) {
            $stockItemConfiguration->setMaxSaleQty(null);
        } elseif ($stockItem->getData(StockItemConfigurationInterface::MAX_SALE_QTY) !== null) {
            $stockItemConfiguration->setMaxSaleQty(
                (float)$stockItem->getData(StockItemConfigurationInterface::MAX_SALE_QTY)
            );
        }

        if ($stockItem->getData('use_config_enable_qty_inc')) {
            $stockItemConfiguration->setEnableQtyIncrements(null);
        } elseif ($stockItem->getData(StockItemConfigurationInterface::ENABLE_QTY_INCREMENTS) !== null) {
            $stockItemConfiguration->setEnableQtyIncrements(
                (bool)$stockItem->getData(StockItemConfigurationInterface::ENABLE_QTY_INCREMENTS)
            );
        }

        if ($stockItem->getData('use_config_' . StockItemConfigurationInterface::QTY_INCREMENTS)) {
            $stockItemConfiguration->setQtyIncrements(null);
        } elseif ($stockItem->getData(StockItemConfigurationInterface::QTY_INCREMENTS) !== null) {
            $stockItemConfiguration->setQtyIncrements(
                (float)$stockItem->getData(StockItemConfigurationInterface::QTY_INCREMENTS)
            );
        }

        $isQtyDecimal = $stockItem->getData(StockItemConfigurationInterface::IS_QTY_DECIMAL) !== null
            ? (bool)$stockItem->getData(StockItemConfigurationInterface::IS_QTY_DECIMAL)
            : (bool)$stockItemConfiguration->isQtyDecimal();
        $stockItemConfiguration->setIsQtyDecimal($isQtyDecimal);
        $isDecimalDivided = $stockItem->getData(StockItemConfigurationInterface::IS_DECIMAL_DIVIDED) !== null
            ? (bool)$stockItem->getData(StockItemConfigurationInterface::IS_DECIMAL_DIVIDED)
            : (bool)$stockItemConfiguration->isDecimalDivided();
        $stockItemConfiguration->setIsDecimalDivided($isDecimalDivided);

        $this->saveStockConfiguration->forStockItem(
            $productSku,
            $this->defaultStockProvider->getId(),
            $stockItemConfiguration
        );

        return $result;
    }
}
