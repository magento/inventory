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
use Magento\InventoryConfigurationApi\Api\Data\StockItemConfigurationInterfaceFactory;
use Magento\InventoryConfigurationApi\Api\SaveStockConfigurationInterface;

/**
 * Save stock item configuration for given product and default stock after stock item was saved successfully.
 */
class SaveStockItemConfigurationPlugin
{
    /**
     * @var StockItemConfigurationInterfaceFactory
     */
    private $stockItemConfigurationInterfaceFactory;

    /**
     * @var SaveStockConfigurationInterface
     */
    private $saveStockConfiguration;

    /**
     * @var GetSkusByProductIdsInterface
     */
    private $getSkusByProductIds;    /**
     * @var DefaultStockProviderInterface
     */
    private $defaultStockProvider;

    /**
     * @param StockItemConfigurationInterfaceFactory $stockItemConfigurationInterfaceFactory
     * @param SaveStockConfigurationInterface $saveStockConfiguration
     * @param GetSkusByProductIdsInterface $getSkusByProductIds
     * @param DefaultStockProviderInterface $defaultStockProvider
     */
    public function __construct(
        StockItemConfigurationInterfaceFactory $stockItemConfigurationInterfaceFactory,
        SaveStockConfigurationInterface $saveStockConfiguration,
        GetSkusByProductIdsInterface $getSkusByProductIds,
        DefaultStockProviderInterface $defaultStockProvider
    ) {
        $this->stockItemConfigurationInterfaceFactory = $stockItemConfigurationInterfaceFactory;
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
    public function afterSave(ItemResourceModel $subject, ItemResourceModel $result, StockItemInterface $stockItem)
    {
        $productId = $stockItem->getData('product_id');
        $skus = $this->getSkusByProductIds->execute([$productId]);
        $productSku = $skus[$productId];

        $stockItemConfiguration = $this->stockItemConfigurationInterfaceFactory->create();
        if ($stockItem->getData('use_config_' . StockItemConfigurationInterface::MANAGE_STOCK)) {
            $stockItemConfiguration->setManageStock(null);
        } else {
            $stockItemConfiguration->setManageStock(
                (bool)$stockItem->getData(StockItemConfigurationInterface::MANAGE_STOCK)
            );
        }

        if ($stockItem->getData('use_config_' . StockItemConfigurationInterface::MIN_QTY)) {
            $stockItemConfiguration->setMinQty(null);
        } else {
            $stockItemConfiguration->setMinQty(
                (float)$stockItem->getData(StockItemConfigurationInterface::MIN_QTY)
            );
        }

        if ($stockItem->getData('use_config_' . StockItemConfigurationInterface::MIN_QTY)) {
            $stockItemConfiguration->setStockThresholdQty(null);
        } else {
            $stockItemConfiguration->setStockThresholdQty(
                (float)$stockItem->getData(StockItemConfigurationInterface::STOCK_THRESHOLD_QTY)
            );
        }

        if ($stockItem->getData('use_config_' . StockItemConfigurationInterface::MIN_SALE_QTY)) {
            $stockItemConfiguration->setMinSaleQty(null);
        } else {
            $stockItemConfiguration->setMinSaleQty(
                (float)$stockItem->getData(StockItemConfigurationInterface::MIN_SALE_QTY)
            );
        }

        if ($stockItem->getData('use_config_' . StockItemConfigurationInterface::MAX_SALE_QTY)) {
            $stockItemConfiguration->setMaxSaleQty(null);
        } else {
            $stockItemConfiguration->setMaxSaleQty(
                (float)$stockItem->getData(StockItemConfigurationInterface::MAX_SALE_QTY)
            );
        }

        if ($stockItem->getData('use_config_enable_qty_inc')) {
            $stockItemConfiguration->setEnableQtyIncrements(null);
        } else {
            $stockItemConfiguration->setEnableQtyIncrements(
                (bool)$stockItem->getData(StockItemConfigurationInterface::ENABLE_QTY_INCREMENTS)
            );
        }

        if ($stockItem->getData('use_config_' . StockItemConfigurationInterface::QTY_INCREMENTS)) {
            $stockItemConfiguration->setQtyIncrements(null);
        } else {
            $stockItemConfiguration->setQtyIncrements(
                (float)$stockItem->getData(StockItemConfigurationInterface::QTY_INCREMENTS)
            );
        }

        $stockItemConfiguration->setIsQtyDecimal(
            (bool)$stockItem->getData(StockItemConfigurationInterface::IS_QTY_DECIMAL)
        );
        $stockItemConfiguration->setIsDecimalDivided(
            (bool)$stockItem->getData(StockItemConfigurationInterface::IS_DECIMAL_DIVIDED)
        );

        $this->saveStockConfiguration->forStockItem(
            $productSku,
            $this->defaultStockProvider->getId(),
            $stockItemConfiguration
        );

        return $result;
    }
}
