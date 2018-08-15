<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurationAdminUi\Plugin\InventoryAdminUi\Ui\SourceDataProvider;

use Magento\InventoryAdminUi\Ui\DataProvider\SourceDataProvider;
use Magento\InventoryConfigurationApi\Api\GetBackordersConfigurationValueInterface;
use Magento\InventoryConfigurationApi\Api\GetNotifyStockQtyConfigurationValueInterface;

/**
 * Customize source form. Add configuration data
 */
class SourceConfigurationPlugin
{
    /**
     * @var GetBackordersConfigurationValueInterface
     */
    private $getBackordersConfigurationValue;

    /**
     * @var GetNotifyStockQtyConfigurationValueInterface
     */
    private $getNotifyStockQtyConfigurationValue;

    /**
     * @param GetBackordersConfigurationValueInterface $getBackordersConfigurationValue
     * @param GetNotifyStockQtyConfigurationValueInterface $getNotifyStockQtyConfigurationValue
     */
    public function __construct(
        GetBackordersConfigurationValueInterface $getBackordersConfigurationValue,
        GetNotifyStockQtyConfigurationValueInterface $getNotifyStockQtyConfigurationValue
    ) {
        $this->getBackordersConfigurationValue = $getBackordersConfigurationValue;
        $this->getNotifyStockQtyConfigurationValue = $getNotifyStockQtyConfigurationValue;
    }

    /**
     * @param SourceDataProvider $subject
     * @param array $data
     * @return array
     */
    public function afterGetData(SourceDataProvider $subject, array $data): array
    {
        if ('inventory_source_form_data_source' === $subject->getName()) {
            foreach ($data as $sourceCode => &$sourceData) {
                $sourceData['inventory_configuration'] = [
                    'backorders' => $this->getBackordersConfigData($sourceCode),
                    'notify_stock_qty' => $this->getNotifyStockQtyConfigData($sourceCode)
                ];
            }
        }
        return $data;
    }

    /**
     * @param string $sourceCode
     * @return array
     */
    private function getBackordersConfigData(string $sourceCode): array
    {
        $globalValue = $this->getBackordersConfigurationValue->forGlobal();
        $stockValue = $this->getBackordersConfigurationValue->forSource($sourceCode);

        return [
            'value' => $stockValue ?? $globalValue,
            'use_config_value' => isset($stockValue) ? "0" : "1",
            'default_value' => $globalValue
        ];
    }

    /**
     * @param string $sourceCode
     * @return array
     */
    private function getNotifyStockQtyConfigData(string $sourceCode): array
    {
        $globalValue = $this->getNotifyStockQtyConfigurationValue->forGlobal();
        $stockValue = $this->getNotifyStockQtyConfigurationValue->forSource($sourceCode);

        return [
            'value' => $stockValue ?? $globalValue,
            'use_config_value' => isset($stockValue) ? "0" : "1",
            'valueFromConfig' => $globalValue
        ];
    }
}
