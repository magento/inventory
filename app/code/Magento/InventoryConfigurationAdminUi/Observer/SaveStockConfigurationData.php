<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurationAdminUi\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\InventoryConfigurationApi\Api\Data\StockItemConfigurationInterfaceFactory;
use Magento\InventoryConfigurationApi\Api\Data\StockItemConfigurationInterface;
use Magento\InventoryConfigurationApi\Api\SaveStockConfigurationInterface;

class SaveStockConfigurationData implements ObserverInterface
{
    /**
     * @var StockItemConfigurationInterfaceFactory
     */
    private $stockItemConfigurationFactory;

    /**
     * @var SaveStockConfigurationInterface
     */
    private $saveStockConfiguration;

    /**
     * @param StockItemConfigurationInterfaceFactory $stockItemConfigurationFactory
     * @param SaveStockConfigurationInterface $saveStockConfiguration
     */
    public function __construct(
        StockItemConfigurationInterfaceFactory $stockItemConfigurationFactory,
        SaveStockConfigurationInterface $saveStockConfiguration
    ) {
        $this->stockItemConfigurationFactory = $stockItemConfigurationFactory;
        $this->saveStockConfiguration = $saveStockConfiguration;
    }

    /**
     * @param EventObserver $observer
     * @return void
     */
    public function execute(EventObserver $observer)
    {
        $stockId = (int)$observer->getStock()->getId();
        $request = $observer->getRequest();

        $configOptions = $request->getParam('inventory_configuration');

        $stockItemConfiguration = $this->stockItemConfigurationFactory->create();

        if ($configOptions[StockItemConfigurationInterface::MANAGE_STOCK]['use_config_value']) {
            $stockItemConfiguration->setManageStock(null);
        } else {
            $stockItemConfiguration->setManageStock(
                (bool)$configOptions[StockItemConfigurationInterface::MANAGE_STOCK]['value']
            );
        }

        if ($configOptions[StockItemConfigurationInterface::MIN_QTY]['use_config_value']) {
            $stockItemConfiguration->setMinQty(null);
        } else {
            $stockItemConfiguration->setMinQty(
                (float)$configOptions[StockItemConfigurationInterface::MIN_QTY]['value']
            );
        }

        if ($configOptions[StockItemConfigurationInterface::MIN_SALE_QTY]['use_config_value']) {
            $stockItemConfiguration->setMinSaleQty(null);
        } else {
            $stockItemConfiguration->setMinSaleQty(
                (float)$configOptions[StockItemConfigurationInterface::MIN_SALE_QTY]['value']
            );
        }

        if ($configOptions[StockItemConfigurationInterface::MAX_SALE_QTY]['use_config_value']) {
            $stockItemConfiguration->setMaxSaleQty(null);
        } else {
            $stockItemConfiguration->setMaxSaleQty(
                (float)$configOptions[StockItemConfigurationInterface::MAX_SALE_QTY]['value']
            );
        }

        if ($configOptions[StockItemConfigurationInterface::ENABLE_QTY_INCREMENTS]['use_config_value']) {
            $stockItemConfiguration->setEnableQtyIncrements(null);
        } else {
            $stockItemConfiguration->setEnableQtyIncrements(
                (bool)$configOptions[StockItemConfigurationInterface::ENABLE_QTY_INCREMENTS]['value']
            );
        }

        if ($configOptions[StockItemConfigurationInterface::QTY_INCREMENTS]['use_config_value']) {
            $stockItemConfiguration->setQtyIncrements(null);
        } else {
            $stockItemConfiguration->setQtyIncrements(
                (float)$configOptions[StockItemConfigurationInterface::QTY_INCREMENTS]['value']
            );
        }

        if ($configOptions[StockItemConfigurationInterface::STOCK_THRESHOLD_QTY]['use_config_value']) {
            $stockItemConfiguration->setStockThresholdQty(null);
        } else {
            $stockItemConfiguration->setStockThresholdQty(
                (float)$configOptions[StockItemConfigurationInterface::STOCK_THRESHOLD_QTY]['value']
            );
        }

        $isQtyDecimal = (bool)$configOptions[StockItemConfigurationInterface::IS_QTY_DECIMAL];
        $stockItemConfiguration->setIsQtyDecimal($isQtyDecimal);
        $isDecimalDivided = (bool)$configOptions[StockItemConfigurationInterface::IS_DECIMAL_DIVIDED];
        $stockItemConfiguration->setIsDecimalDivided($isDecimalDivided);

        $this->saveStockConfiguration->forStock($stockId, $stockItemConfiguration);
    }
}
