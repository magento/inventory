<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfiguration\Model;

use Magento\InventoryConfigurationApi\Api\Data\StockItemConfigurationInterface;
use Magento\InventoryConfigurationApi\Api\Data\StockItemConfigurationInterfaceFactory;
use Magento\InventoryConfigurationApi\Api\GetStockConfigurationInterface;
use Magento\InventoryConfiguration\Model\ResourceModel\GetStockConfigurationData;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;

class GetStockConfiguration implements GetStockConfigurationInterface
{
    /**
     * @var StockItemConfigurationInterfaceFactory
     */
    private $stockItemConfigurationFactory;

    /**
     * @var GetStockConfigurationData
     */
    private $getStockConfigurationData;

    /**
     * @var DataObjectHelper
     */
    private $dataObjectHelper;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @param StockItemConfigurationInterfaceFactory $stockItemConfigurationFactory
     * @param GetStockConfigurationData $getStockConfigurationData
     * @param DataObjectHelper $dataObjectHelper
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        StockItemConfigurationInterfaceFactory $stockItemConfigurationFactory,
        GetStockConfigurationData $getStockConfigurationData,
        DataObjectHelper $dataObjectHelper,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->stockItemConfigurationFactory = $stockItemConfigurationFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->getStockConfigurationData = $getStockConfigurationData;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @inheritdoc
     */
    public function forStockItem(string $sku, int $stockId): StockItemConfigurationInterface
    {
        $stockConfigurationData = $this->getStockConfigurationData->execute($stockId, $sku);
        /** @var StockItemConfigurationInterface $stockItemConfiguration */
        $stockItemConfiguration = $this->stockItemConfigurationFactory->create();
        $this->dataObjectHelper->populateWithArray(
            $stockItemConfiguration,
            $stockConfigurationData,
            StockItemConfigurationInterface::class
        );

        return $stockItemConfiguration;
    }

    /**
     * @inheritdoc
     */
    public function forStock(int $stockId): StockItemConfigurationInterface
    {
        $stockConfigurationData = $this->getStockConfigurationData->execute($stockId);
        /** @var StockItemConfigurationInterface $stockItemConfiguration */
        $stockItemConfiguration = $this->stockItemConfigurationFactory->create();
        $this->dataObjectHelper->populateWithArray(
            $stockItemConfiguration,
            $stockConfigurationData,
            StockItemConfigurationInterface::class
        );

        return $stockItemConfiguration;
    }

    /**
     * @inheritdoc
     */
    public function forGlobal(): StockItemConfigurationInterface
    {
        /** @var StockItemConfigurationInterface $stockItemConfiguration */
        $stockItemConfiguration = $this->stockItemConfigurationFactory->create();

        $stockItemConfiguration->setMinQty(
            (float)$this->scopeConfig->getValue(StockItemConfigurationInterface::XML_PATH_MIN_QTY)
        );

        //TODO: min sale qty => string in global config
        $stockItemConfiguration->setMinSaleQty(
            (float)$this->scopeConfig->getValue(StockItemConfigurationInterface::XML_PATH_MIN_SALE_QTY)
        );

        $stockItemConfiguration->setMaxSaleQty(
            (float)$this->scopeConfig->getValue(StockItemConfigurationInterface::XML_PATH_MAX_SALE_QTY)
        );

        $stockItemConfiguration->setEnableQtyIncrements(
            (bool)$this->scopeConfig->getValue(StockItemConfigurationInterface::XML_PATH_ENABLE_QTY_INCREMENTS)
        );

        $stockItemConfiguration->setQtyIncrements(
            (float)$this->scopeConfig->getValue(StockItemConfigurationInterface::XML_PATH_QTY_INCREMENTS)
        );

        $stockItemConfiguration->setManageStock(
            (bool)$this->scopeConfig->getValue(StockItemConfigurationInterface::XML_PATH_MANAGE_STOCK)
        );

        $stockItemConfiguration->setStockThresholdQty(
            (float)$this->scopeConfig->getValue(StockItemConfigurationInterface::XML_PATH_STOCK_THRESHOLD_QTY)
        );

        return $stockItemConfiguration;
    }
}
