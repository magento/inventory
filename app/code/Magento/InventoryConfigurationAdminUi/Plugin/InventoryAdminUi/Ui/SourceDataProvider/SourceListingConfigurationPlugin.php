<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurationAdminUi\Plugin\InventoryAdminUi\Ui\SourceDataProvider;

use Magento\InventoryAdminUi\Ui\DataProvider\SourceDataProvider;
use Magento\InventoryConfigurationAdminUi\Model\ResourceModel\GetStockIdsBySourceCode;
use Magento\InventoryConfigurationApi\Api\Data\SourceItemConfigurationInterface;
use Magento\InventoryConfigurationApi\Api\GetSourceConfigurationInterface;

/**
 * Add "Backorders" and "Notify Qty For Below" configuration to sources.
 */
class SourceListingConfigurationPlugin
{
    /**
     * @var GetSourceConfigurationInterface
     */
    private $getSourceConfiguration;

    /**
     * @var GetStockIdsBySourceCode
     */
    private $getStockIdsBySourceCode;

    /**
     * @param GetSourceConfigurationInterface $getSourceConfiguration
     * @param GetStockIdsBySourceCode $getStockIdsBySourceCode
     */
    public function __construct(
        GetSourceConfigurationInterface $getSourceConfiguration,
        GetStockIdsBySourceCode $getStockIdsBySourceCode
    ) {
        $this->getSourceConfiguration = $getSourceConfiguration;
        $this->getStockIdsBySourceCode = $getStockIdsBySourceCode;
    }

    /**
     * @param SourceDataProvider $subject
     * @param array $data
     * @return array
     */
    public function afterGetData(SourceDataProvider $subject, array $data): array
    {
        if ('inventory_source_listing_data_source' === $subject->getName() && $data['totalRecords'] > 0) {
            foreach ($data['items'] as &$source) {
                $globalSourceConfiguration = $this->getSourceConfiguration->forGlobal();
                $sourceConfiguration = $this->getSourceConfiguration->forSource($source['source_code']);
                $source['backorders'] = $this->getBackordersConfigData(
                    $sourceConfiguration,
                    $globalSourceConfiguration
                );
                $source['notify_stock_qty'] = $this->getNotifyStockQtyConfigData(
                    $sourceConfiguration,
                    $globalSourceConfiguration
                );
                $source['stock_ids'] = $this->getStockIds($source['source_code']);
            }
        }

        return $data;
    }

    /**
     * @param SourceItemConfigurationInterface $sourceConfiguration
     * @param SourceItemConfigurationInterface $globalSourceConfiguration
     * @return int
     */
    private function getBackordersConfigData(
        SourceItemConfigurationInterface $sourceConfiguration,
        SourceItemConfigurationInterface $globalSourceConfiguration
    ): int {
        $globalValue = $globalSourceConfiguration->getBackorders();
        $sourceValue = $sourceConfiguration->getBackorders();

        return $sourceValue !== null ? (int)$sourceValue : (int)$globalValue;
    }

    /**
     * @param SourceItemConfigurationInterface $sourceConfiguration
     * @param SourceItemConfigurationInterface $globalSourceConfiguration
     * @return int
     */
    private function getNotifyStockQtyConfigData(
        SourceItemConfigurationInterface $sourceConfiguration,
        SourceItemConfigurationInterface $globalSourceConfiguration
    ): int {
        $globalValue = $globalSourceConfiguration->getNotifyStockQty();
        $sourceValue = $sourceConfiguration->getNotifyStockQty();

        return $sourceValue !== null ? (int)$sourceValue : (int)$globalValue;
    }

    /**
     * @param string $sourceCode
     * @return string
     */
    private function getStockIds(string $sourceCode): string
    {
        return implode(',', $this->getStockIdsBySourceCode->execute($sourceCode));
    }
}