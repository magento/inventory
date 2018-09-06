<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurationAdminUi\Plugin\InventoryAdminUi\Ui\SourceDataProvider;

use Magento\InventoryAdminUi\Ui\DataProvider\SourceDataProvider;
use Magento\InventoryConfigurationApi\Api\GetSourceConfigurationInterface;
use Magento\InventoryConfigurationApi\Api\Data\SourceItemConfigurationInterface;

/**
 * Customize source form. Add configuration data
 */
class SourceConfigurationPlugin
{
    /**
     * @var GetSourceConfigurationInterface
     */
    private $getSourceConfiguration;

    /**
     * @param GetSourceConfigurationInterface $getSourceConfiguration
     */
    public function __construct(
        GetSourceConfigurationInterface $getSourceConfiguration
    ) {
        $this->getSourceConfiguration = $getSourceConfiguration;
    }

    /**
     * @param SourceDataProvider $subject
     * @param array $data
     * @return array
     */
    public function afterGetData(SourceDataProvider $subject, array $data): array
    {
        if ('inventory_source_form_data_source' === $subject->getName()) {
            if ($data) {
                $data = $this->populateDataForExistingSource($data);
            } else {
                $data = $this->populateDataForNewSource();
            }
        }
        return $data;
    }

    /**
     * @param SourceItemConfigurationInterface $sourceConfiguration
     * @param SourceItemConfigurationInterface $globalSourceConfiguration
     * @return array
     */
    private function getBackordersConfigData(
        SourceItemConfigurationInterface $sourceConfiguration,
        SourceItemConfigurationInterface $globalSourceConfiguration
    ): array {
        $globalValue = $globalSourceConfiguration->getBackorders();
        $sourceValue = $sourceConfiguration->getBackorders();

        return [
            'value' => $sourceValue ?? $globalValue,
            'use_config_value' => isset($sourceValue) ? "0" : "1",
            'default_value' => $globalValue
        ];
    }

    /**
     * @param SourceItemConfigurationInterface $sourceConfiguration
     * @param SourceItemConfigurationInterface $globalSourceConfiguration
     * @return array
     */
    private function getNotifyStockQtyConfigData(
        SourceItemConfigurationInterface $sourceConfiguration,
        SourceItemConfigurationInterface $globalSourceConfiguration
    ): array {
        $globalValue = $globalSourceConfiguration->getNotifyStockQty();
        $sourceValue = $sourceConfiguration->getNotifyStockQty();

        return [
            'value' => $sourceValue ?? $globalValue,
            'use_config_value' => isset($sourceValue) ? "0" : "1",
            'default_value' => $globalValue
        ];
    }

    /**
     * @param array $data
     * @return array
     */
    private function populateDataForExistingSource(array $data): array
    {
        $globalSourceConfiguration = $this->getSourceConfiguration->forGlobal();
        foreach ($data as $sourceCode => &$sourceData) {
            $sourceConfiguration = $this->getSourceConfiguration->forSource($sourceCode);
            $sourceData['inventory_configuration'] = [
                'backorders' => $this->getBackordersConfigData($sourceConfiguration, $globalSourceConfiguration),
                'notify_stock_qty' => $this->getNotifyStockQtyConfigData(
                    $sourceConfiguration,
                    $globalSourceConfiguration
                )
            ];
        }

        return $data;
    }

    /**
     * @return array
     */
    private function populateDataForNewSource(): array
    {
        $globalSourceConfiguration = $this->getSourceConfiguration->forGlobal();
        $data[null] = [
            'inventory_configuration' => [
                'backorders' => [
                    'value' => (int)$globalSourceConfiguration->getBackorders(),
                    'use_config_value' => "1",
                    'default_value' => (int)$globalSourceConfiguration->getBackorders()
                ],
                'notify_stock_qty' => [
                    'value' => $globalSourceConfiguration->getNotifyStockQty(),
                    'use_config_value' => "1",
                    'default_value' => $globalSourceConfiguration->getNotifyStockQty()
                ],
            ],
        ];

        return $data;
    }
}
