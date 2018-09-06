<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurationAdminUi\Plugin\InventoryAdminUi\Ui\StockDataProvider;

use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Serialize\JsonValidator;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\InventoryAdminUi\Ui\DataProvider\StockDataProvider;
use Magento\InventoryConfigurationApi\Api\Data\StockItemConfigurationInterface;
use Magento\InventoryConfigurationApi\Api\GetStockConfigurationInterface;

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
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var JsonValidator
     */
    private $jsonValidator;

    /**
     * @var Json
     */
    private $serializer;

    /**
     * @param GetStockConfigurationInterface $getStockConfiguration
     * @param ScopeConfigInterface $scopeConfig
     * @param JsonValidator $jsonValidator
     * @param Json $serializer
     */
    public function __construct(
        GetStockConfigurationInterface $getStockConfiguration,
        ScopeConfigInterface $scopeConfig,
        JsonValidator $jsonValidator,
        Json $serializer
    ) {
        $this->getStockConfiguration = $getStockConfiguration;
        $this->scopeConfig = $scopeConfig;
        $this->jsonValidator = $jsonValidator;
        $this->serializer = $serializer;
    }

    /**
     * @param StockDataProvider $subject
     * @param array $data
     * @return array
     */
    public function afterGetData(StockDataProvider $subject, array $data): array
    {
        if ('inventory_stock_form_data_source' === $subject->getName()) {
            if ($data) {
                $data = $this->populateDataForExistingStock($data);
            } else {
                $data = $this->populateDataForNewStock();
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
            'value' => $stockValue !== null ? (int)$stockValue : (int)$globalValue,
            'use_config_value' => isset($stockValue) ? "0" : "1",
            'default_value' => (int)$globalValue
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
            'value' => $stockValue !== null ? (int)$stockValue : (int)$globalValue,
            'use_config_value' => isset($stockValue) ? "0" : "1",
            'default_value' => (int)$globalValue
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

    /**
     * @param StockItemConfigurationInterface $stockConfiguration
     * @return array
     */
    private function getMinSaleQtyConfigData(StockItemConfigurationInterface $stockConfiguration): array
    {
        $stockValue = $stockConfiguration->getMinSaleQty();
        $result = [
            'value' => $stockValue ?? null,
            'use_config_value' => isset($stockValue) ? "0" : "1",
        ];
        $result['min_qty_allowed_in_shopping_cart'] = $this->getMinSaleQtyDefaultConfig();

        return $result;
    }

    /**
     * @param array $data
     * @return mixed
     */
    private function populateDataForExistingStock(array $data): array
    {
        $globalStockConfiguration = $this->getStockConfiguration->forGlobal();
        foreach ($data as $stockId => &$stockData) {
            $stockConfiguration = $this->getStockConfiguration->forStock($stockId);
            $stockData['inventory_configuration'] = [
                'manage_stock' => $this->getManageStockConfigData($stockConfiguration, $globalStockConfiguration),
                'min_qty' => $this->getMinQtyConfigData($stockConfiguration, $globalStockConfiguration),
                'max_sale_qty' => $this->getMaxSaleQtyConfigData($stockConfiguration, $globalStockConfiguration),
                'enable_qty_increments' => $this->getEnableQtyIncrementsConfigData(
                    $stockConfiguration,
                    $globalStockConfiguration
                ),
                'qty_increments' => $this->getQtyIncrementsConfigData(
                    $stockConfiguration,
                    $globalStockConfiguration
                ),
                'stock_threshold_qty' => $this->getStockThresholdQtyConfigData(
                    $stockConfiguration,
                    $globalStockConfiguration
                ),
                'min_sale_qty' => $this->getMinSaleQtyConfigData($stockConfiguration),
                'is_qty_decimal' => (int)$stockConfiguration->isQtyDecimal(),
                'is_decimal_divided' => (int)$stockConfiguration->isDecimalDivided(),
            ];
        }
        return $data;
    }

    /**
     * @return array
     */
    private function populateDataForNewStock(): array
    {
        $globalStockConfiguration = $this->getStockConfiguration->forGlobal();
        $data[null] = [
            'inventory_configuration' => [
                'manage_stock' => [
                    'value' => (int)$globalStockConfiguration->isManageStock(),
                    'use_config_value' => "1",
                    'default_value' => (int)$globalStockConfiguration->isManageStock()
                ],
                'min_qty' => [
                    'value' => $globalStockConfiguration->getMinQty(),
                    'use_config_value' => "1",
                    'default_value' => $globalStockConfiguration->getMinQty()
                ],
                'max_sale_qty' => [
                    'value' => $globalStockConfiguration->getMaxSaleQty(),
                    'use_config_value' => "1",
                    'default_value' => $globalStockConfiguration->getMaxSaleQty()
                ],
                'enable_qty_increments' =>[
                    'value' => $globalStockConfiguration->isEnableQtyIncrements(),
                    'use_config_value' => "1",
                    'default_value' => $globalStockConfiguration->isEnableQtyIncrements()
                ],
                'qty_increments' => [
                    'value' => $globalStockConfiguration->getQtyIncrements(),
                    'use_config_value' => "1",
                    'default_value' => $globalStockConfiguration->getQtyIncrements()
                ],
                'stock_threshold_qty' => [
                    'value' => $globalStockConfiguration->getStockThresholdQty(),
                    'use_config_value' => "1",
                    'default_value' => $globalStockConfiguration->getStockThresholdQty()
                ],
                'min_sale_qty' => [
                    'min_qty_allowed_in_shopping_cart' => $this->getMinSaleQtyDefaultConfig()
                ],
            ],
        ];

        return $data;
    }

    /**
     * @return array
     */
    private function getMinSaleQtyDefaultConfig(): array
    {
        if (empty($this->scopeConfig->getValue(StockItemConfigurationInterface::XML_PATH_MIN_SALE_QTY))) {
            return [];
        }
        $minSaleQtyData = $this->scopeConfig->getValue(StockItemConfigurationInterface::XML_PATH_MIN_SALE_QTY);
        if (is_string($minSaleQtyData) && $this->jsonValidator->isValid($minSaleQtyData)) {
            // Set data source for dynamicRows minimum qty allowed in shopping cart
            $unserializedMinSaleQty = $this->serializer->unserialize($minSaleQtyData);
            if (is_array($unserializedMinSaleQty)) {
                $minSaleQtyData = array_map(
                    function ($group, $qty) {
                        return [
                            StockItemInterface::CUSTOMER_GROUP_ID => $group,
                            StockItemInterface::MIN_SALE_QTY => $qty
                        ];
                    },
                    array_keys($unserializedMinSaleQty),
                    array_values($unserializedMinSaleQty)
                );
            } else {
                $minSaleQtyData = [$unserializedMinSaleQty];
            }
        }

        return $minSaleQtyData;
    }
}
