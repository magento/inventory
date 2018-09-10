<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurationAdminUi\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\InventoryConfigurationAdminUi\Model\ResourceModel\GetStockIdsBySourceCode;
use Magento\InventoryConfigurationApi\Api\Data\StockItemConfigurationInterface;
use Magento\InventoryConfigurationApi\Api\Data\StockItemConfigurationInterfaceFactory;
use Magento\InventoryConfigurationApi\Api\SaveSourceConfigurationInterface;
use Magento\InventoryConfigurationApi\Api\SaveStockConfigurationInterface;

/**
 * Save stock item configuration for given product.
 */
class SaveStockItemConfigurationData implements ObserverInterface
{
    /**
     * @var StockItemConfigurationInterfaceFactory
     */
    private $stockItemConfigurationFactory;

    /**
     * @var SaveSourceConfigurationInterface
     */
    private $saveStockConfiguration;

    /**
     * @var GetStockIdsBySourceCode
     */
    private $getStockIdsBySourceCode;

    /**
     * @param StockItemConfigurationInterfaceFactory $stockItemConfigurationFactory
     * @param SaveStockConfigurationInterface $saveStockConfiguration
     * @param GetStockIdsBySourceCode $getStockIdsBySourceCodes
     */
    public function __construct(
        StockItemConfigurationInterfaceFactory $stockItemConfigurationFactory,
        SaveStockConfigurationInterface $saveStockConfiguration,
        GetStockIdsBySourceCode $getStockIdsBySourceCodes
    ) {
        $this->stockItemConfigurationFactory = $stockItemConfigurationFactory;
        $this->saveStockConfiguration = $saveStockConfiguration;
        $this->getStockIdsBySourceCode = $getStockIdsBySourceCodes;
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        $sku = $observer->getProduct()->getSku();
        $request = $observer->getController()->getRequest();
        $product = $request->getParam('product', []);
        $stockData = $product['stock_data'] ?? [];

        if (!$stockData || empty($stockData['stock_id'])) {
            return;
        }

        $stockItemConfiguration = $this->stockItemConfigurationFactory->create();
        if ($stockData['use_config_' . StockItemConfigurationInterface::MANAGE_STOCK]) {
            $stockItemConfiguration->setManageStock(null);
        } else {
            $stockItemConfiguration->setManageStock(
                (bool)$stockData[StockItemConfigurationInterface::MANAGE_STOCK]
            );
        }

        if ($stockData['use_config_' . StockItemConfigurationInterface::MIN_QTY]) {
            $stockItemConfiguration->setMinQty(null);
        } else {
            $stockItemConfiguration->setMinQty(
                (float)$stockData[StockItemConfigurationInterface::MIN_QTY]
            );
        }

        if ($stockData['use_config_' . StockItemConfigurationInterface::MIN_QTY]) {
            $stockItemConfiguration->setStockThresholdQty(null);
        } else {
            $stockItemConfiguration->setStockThresholdQty(
                (float)$stockData[StockItemConfigurationInterface::STOCK_THRESHOLD_QTY]
            );
        }

        if ($stockData['use_config_' . StockItemConfigurationInterface::MIN_SALE_QTY]) {
            $stockItemConfiguration->setMinSaleQty(null);
        } else {
            $stockItemConfiguration->setMinSaleQty(
                (float)$stockData[StockItemConfigurationInterface::MIN_SALE_QTY]
            );
        }

        if ($stockData['use_config_' . StockItemConfigurationInterface::MAX_SALE_QTY]) {
            $stockItemConfiguration->setMaxSaleQty(null);
        } else {
            $stockItemConfiguration->setMaxSaleQty(
                (float)$stockData[StockItemConfigurationInterface::MAX_SALE_QTY]
            );
        }

        if ($stockData['use_config_enable_qty_inc']) {
            $stockItemConfiguration->setEnableQtyIncrements(null);
        } else {
            $stockItemConfiguration->setEnableQtyIncrements(
                (bool)$stockData[StockItemConfigurationInterface::ENABLE_QTY_INCREMENTS]
            );
        }

        if ($stockData['use_config_' . StockItemConfigurationInterface::QTY_INCREMENTS]) {
            $stockItemConfiguration->setQtyIncrements(null);
        } else {
            $stockItemConfiguration->setQtyIncrements(
                (float)$stockData[StockItemConfigurationInterface::QTY_INCREMENTS]
            );
        }

        $isQtyDecimal = (bool)$stockData[StockItemConfigurationInterface::IS_QTY_DECIMAL];
        $stockItemConfiguration->setIsQtyDecimal($isQtyDecimal);
        $isDecimalDivided = (bool)$stockData[StockItemConfigurationInterface::IS_DECIMAL_DIVIDED];
        $stockItemConfiguration->setIsDecimalDivided($isDecimalDivided);

        $this->saveStockConfiguration->forStockItem((string)$sku, (int)$stockData['stock_id'], $stockItemConfiguration);
    }
}
