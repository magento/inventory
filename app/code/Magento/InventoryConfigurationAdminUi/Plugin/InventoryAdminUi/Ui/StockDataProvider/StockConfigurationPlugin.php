<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurationAdminUi\Plugin\InventoryAdminUi\Ui\StockDataProvider;

use Magento\InventoryAdminUi\Ui\DataProvider\StockDataProvider;
use Magento\InventoryConfigurationApi\Api\GetManageStockConfigurationValueInterface;
use Magento\InventoryConfigurationApi\Api\GetMinQtyConfigurationValueInterface;
use Magento\InventoryConfigurationApi\Api\GetMaxSaleQtyConfigurationValueInterface;
use Magento\InventoryConfigurationApi\Api\GetEnableQtyIncrementsConfigurationValueInterface;
use Magento\InventoryConfigurationApi\Api\GetQtyIncrementsConfigurationValueInterface;
use Magento\InventoryConfigurationApi\Api\GetAutoReturnToStockConfigurationValueInterface;
use Magento\InventoryConfigurationApi\Api\GetStockThresholdQtyConfigurationValueInterface;

/**
 * Customize stock form. Add configuration data
 */
class StockConfigurationPlugin
{
    /**
     * @var GetManageStockConfigurationValueInterface
     */
    private $getManageStockConfigurationValue;

    /**
     * @var GetMinQtyConfigurationValueInterface
     */
    private $getMinQtyConfigurationValue;

    /**
     * @var GetMaxSaleQtyConfigurationValueInterface
     */
    private $getMaxSaleQtyConfigurationValue;

    /**
     * @var GetEnableQtyIncrementsConfigurationValueInterface
     */
    private $getEnableQtyIncrementsConfigurationValue;

    /**
     * @var GetQtyIncrementsConfigurationValueInterface
     */
    private $getQtyIncrementsConfigurationValue;

    /**
     * @var GetAutoReturnToStockConfigurationValueInterface
     */
    private $getAutoReturnToStockConfigurationValue;

    /**
     * @var GetStockThresholdQtyConfigurationValueInterface
     */
    private $getStockThresholdQtyConfigurationValue;

    /**
     * @param GetManageStockConfigurationValueInterface $getManageStockConfigurationValue
     * @param GetMinQtyConfigurationValueInterface $getMinQtyConfigurationValue
     * @param GetMaxSaleQtyConfigurationValueInterface $getMaxSaleQtyConfigurationValue
     * @param GetEnableQtyIncrementsConfigurationValueInterface $getEnableQtyIncrementsConfigurationValue
     * @param GetQtyIncrementsConfigurationValueInterface $getQtyIncrementsConfigurationValue
     * @param GetAutoReturnToStockConfigurationValueInterface $getAutoReturnToStockConfigurationValue
     * @param GetStockThresholdQtyConfigurationValueInterface $getStockThresholdQtyConfigurationValue
     */
    public function __construct(
        GetManageStockConfigurationValueInterface $getManageStockConfigurationValue,
        GetMinQtyConfigurationValueInterface $getMinQtyConfigurationValue,
        GetMaxSaleQtyConfigurationValueInterface $getMaxSaleQtyConfigurationValue,
        GetEnableQtyIncrementsConfigurationValueInterface $getEnableQtyIncrementsConfigurationValue,
        GetQtyIncrementsConfigurationValueInterface $getQtyIncrementsConfigurationValue,
        GetAutoReturnToStockConfigurationValueInterface $getAutoReturnToStockConfigurationValue,
        GetStockThresholdQtyConfigurationValueInterface $getStockThresholdQtyConfigurationValue
    ) {
        $this->getManageStockConfigurationValue = $getManageStockConfigurationValue;
        $this->getMinQtyConfigurationValue = $getMinQtyConfigurationValue;
        $this->getMaxSaleQtyConfigurationValue = $getMaxSaleQtyConfigurationValue;
        $this->getEnableQtyIncrementsConfigurationValue = $getEnableQtyIncrementsConfigurationValue;
        $this->getQtyIncrementsConfigurationValue = $getQtyIncrementsConfigurationValue;
        $this->getAutoReturnToStockConfigurationValue = $getAutoReturnToStockConfigurationValue;
        $this->getStockThresholdQtyConfigurationValue = $getStockThresholdQtyConfigurationValue;
    }

    /**
     * @param StockDataProvider $subject
     * @param array $data
     * @return array
     */
    public function afterGetData(StockDataProvider $subject, array $data): array
    {
        if ('inventory_stock_form_data_source' === $subject->getName()) {
            foreach ($data as $stockId => &$stockData) {
                $stockData['inventory_configuration'] = [
                    'manage_stock' => $this->getManageStockConfigData($stockId),
                    'min_qty' => $this->getMinQtyConfigData($stockId),
                    'max_sale_qty' => $this->getMaxSaleQtyConfigData($stockId),
                    'enable_qty_increments' => $this->getEnableQtyIncrementsConfigData($stockId),
                    'qty_increments' => $this->getQtyIncrementsConfigData($stockId),
                    'auto_return_to_stock' => $this->getAutoReturnToStockConfigData($stockId),
                    'stock_threshold_qty' => $this->getStockThresholdQtyConfigData($stockId)
                ];
            }
        }
        return $data;
    }

    /**
     * @param int $stockId
     * @return array
     */
    private function getManageStockConfigData(int $stockId): array
    {
        $globalValue = $this->getManageStockConfigurationValue->forGlobal();
        $stockValue = $this->getManageStockConfigurationValue->forStock($stockId);

        return [
            'value' => $stockValue ?? $globalValue,
            'use_config_value' => isset($stockValue) ? "0" : "1",
            'valueFromConfig' => $globalValue
        ];
    }

    /**
     * @param int $stockId
     * @return array
     */
    private function getMinQtyConfigData(int $stockId): array
    {
        $globalValue = $this->getMinQtyConfigurationValue->forGlobal();
        $stockValue = $this->getMinQtyConfigurationValue->forStock($stockId);

        return [
            'value' => $stockValue ?? $globalValue,
            'use_config_value' => isset($stockValue) ? "0" : "1",
            'valueFromConfig' => $globalValue
        ];
    }

    /**
     * @param int $stockId
     * @return array
     */
    private function getMaxSaleQtyConfigData(int $stockId): array
    {
        $globalValue = $this->getMaxSaleQtyConfigurationValue->forGlobal();
        $stockValue = $this->getMaxSaleQtyConfigurationValue->forStock($stockId);

        return [
            'value' => $stockValue ?? $globalValue,
            'use_config_value' => isset($stockValue) ? "0" : "1",
            'valueFromConfig' => $globalValue
        ];
    }

    /**
     * @param int $stockId
     * @return array
     */
    private function getEnableQtyIncrementsConfigData(int $stockId): array
    {
        $globalValue = $this->getEnableQtyIncrementsConfigurationValue->forGlobal();
        $stockValue = $this->getEnableQtyIncrementsConfigurationValue->forStock($stockId);

        return [
            'value' => $stockValue ?? $globalValue,
            'use_config_value' => isset($stockValue) ? "0" : "1",
            'valueFromConfig' => $globalValue
        ];
    }

    /**
     * @param int $stockId
     * @return array
     */
    private function getQtyIncrementsConfigData(int $stockId): array
    {
        $globalValue = $this->getQtyIncrementsConfigurationValue->forGlobal();
        $stockValue = $this->getQtyIncrementsConfigurationValue->forStock($stockId);

        return [
            'value' => $stockValue ?? $globalValue,
            'use_config_value' => isset($stockValue) ? "0" : "1",
            'valueFromConfig' => $globalValue
        ];
    }

    /**
     * @param int $stockId
     * @return array
     */
    private function getAutoReturnToStockConfigData(int $stockId): array
    {
        $globalValue = $this->getAutoReturnToStockConfigurationValue->forGlobal();
        $stockValue = $this->getAutoReturnToStockConfigurationValue->forStock($stockId);

        return [
            'value' => $stockValue ?? $globalValue,
            'use_config_value' => isset($stockValue) ? "0" : "1",
            'valueFromConfig' => $globalValue
        ];
    }

    /**
     * @param int $stockId
     * @return array
     */
    private function getStockThresholdQtyConfigData(int $stockId): array
    {
        $globalValue = $this->getStockThresholdQtyConfigurationValue->forGlobal();
        $stockValue = $this->getStockThresholdQtyConfigurationValue->forStock($stockId);

        return [
            'value' => $stockValue ?? $globalValue,
            'use_config_value' => isset($stockValue) ? "0" : "1",
            'valueFromConfig' => $globalValue
        ];
    }
}
