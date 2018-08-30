<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurationAdminUi\Plugin\InventoryAdminUi\Ui\StockDataProvider;

use Magento\InventoryAdminUi\Ui\DataProvider\StockDataProvider;
use Magento\InventoryConfigurationApi\Api\GetStockConfigurationInterface;
use Magento\InventoryConfigurationApi\Api\Data\StockItemConfigurationInterface;

/**
 * Customize stock form. Add configuration data
 */
class StockConfigurationPlugin
{
    /**
     * @var GetStockConfigurationInterface
     */
    private $getStockConfiguration;

    /**
     * @param GetStockConfigurationInterface $getStockConfiguration
     */
    public function __construct(
        GetStockConfigurationInterface $getStockConfiguration
    ) {
        $this->getStockConfiguration = $getStockConfiguration;
    }

    /**
     * @param StockDataProvider $subject
     * @param array $data
     * @return array
     */
    public function afterGetData(StockDataProvider $subject, array $data): array
    {
        if ('inventory_stock_form_data_source' === $subject->getName()) {
            $globalSourceConfiguration = $this->getStockConfiguration->forGlobal();
            foreach ($data as $stockId => &$stockData) {
                $stockConfiguration = $this->getStockConfiguration->forStock($stockId);
                $stockData['inventory_configuration'] = [
                    'manage_stock' => $this->getManageStockConfigData($stockConfiguration, $globalSourceConfiguration),
                    'min_qty' => $this->getMinQtyConfigData($stockConfiguration, $globalSourceConfiguration),
                    'max_sale_qty' => $this->getMaxSaleQtyConfigData($stockConfiguration, $globalSourceConfiguration),
                    'enable_qty_increments' => $this->getEnableQtyIncrementsConfigData(
                        $stockConfiguration,
                        $globalSourceConfiguration
                    ),
                    'qty_increments' => $this->getQtyIncrementsConfigData(
                        $stockConfiguration,
                        $globalSourceConfiguration
                    ),
                    'stock_threshold_qty' => $this->getStockThresholdQtyConfigData(
                        $stockConfiguration,
                        $globalSourceConfiguration
                    )
                ];
            }
        }
        return $data;
    }

    /**
     * @param StockItemConfigurationInterface $stockConfiguration
     * @param StockItemConfigurationInterface $globalStockConfiguration
     * @return array
     */
    private function getManageStockConfigData(
        StockItemConfigurationInterface $stockConfiguration,
        StockItemConfigurationInterface $globalStockConfiguration
    ): array {
        $globalValue = $globalStockConfiguration->isManageStock();
        $stockValue = $stockConfiguration->isManageStock();

        return [
            'value' => $stockValue ?? $globalValue,
            'use_config_value' => isset($stockValue) ? "0" : "1",
            'default_value' => $globalValue
        ];
    }

    /**
     * @param StockItemConfigurationInterface $stockConfiguration
     * @param StockItemConfigurationInterface $globalStockConfiguration
     * @return array
     */
    private function getMinQtyConfigData(
        StockItemConfigurationInterface $stockConfiguration,
        StockItemConfigurationInterface $globalStockConfiguration
    ): array {
        $globalValue = $globalStockConfiguration->getMinQty();
        $stockValue = $stockConfiguration->getMinQty();

        return [
            'value' => $stockValue ?? $globalValue,
            'use_config_value' => isset($stockValue) ? "0" : "1",
            'default_value' => $globalValue
        ];
    }

    /**
     * @param StockItemConfigurationInterface $stockConfiguration
     * @param StockItemConfigurationInterface $globalStockConfiguration
     * @return array
     */
    private function getMaxSaleQtyConfigData(
        StockItemConfigurationInterface $stockConfiguration,
        StockItemConfigurationInterface $globalStockConfiguration
    ): array {
        $globalValue = $globalStockConfiguration->getMaxSaleQty();
        $stockValue = $stockConfiguration->getMaxSaleQty();

        return [
            'value' => $stockValue ?? $globalValue,
            'use_config_value' => isset($stockValue) ? "0" : "1",
            'default_value' => $globalValue
        ];
    }

    /**
     * @param StockItemConfigurationInterface $stockConfiguration
     * @param StockItemConfigurationInterface $globalStockConfiguration
     * @return array
     */
    private function getEnableQtyIncrementsConfigData(
        StockItemConfigurationInterface $stockConfiguration,
        StockItemConfigurationInterface $globalStockConfiguration
    ): array {
        $globalValue = $globalStockConfiguration->isEnableQtyIncrements();
        $stockValue = $stockConfiguration->isEnableQtyIncrements();

        return [
            'value' => $stockValue ?? $globalValue,
            'use_config_value' => isset($stockValue) ? "0" : "1",
            'default_value' => $globalValue
        ];
    }

    /**
     * @param StockItemConfigurationInterface $stockConfiguration
     * @param StockItemConfigurationInterface $globalStockConfiguration
     * @return array
     */
    private function getQtyIncrementsConfigData(
        StockItemConfigurationInterface $stockConfiguration,
        StockItemConfigurationInterface $globalStockConfiguration
    ): array {
        $globalValue = $globalStockConfiguration->getQtyIncrements();
        $stockValue = $stockConfiguration->getQtyIncrements();

        return [
            'value' => $stockValue ?? $globalValue,
            'use_config_value' => isset($stockValue) ? "0" : "1",
            'default_value' => $globalValue
        ];
    }

    /**
     * @param StockItemConfigurationInterface $stockConfiguration
     * @param StockItemConfigurationInterface $globalStockConfiguration
     * @return array
     */
    private function getStockThresholdQtyConfigData(
        StockItemConfigurationInterface $stockConfiguration,
        StockItemConfigurationInterface $globalStockConfiguration
    ): array {
        $globalValue = $globalStockConfiguration->getStockThresholdQty();
        $stockValue = $stockConfiguration->getStockThresholdQty();

        return [
            'value' => $stockValue ?? $globalValue,
            'use_config_value' => isset($stockValue) ? "0" : "1",
            'default_value' => $globalValue
        ];
    }
}
