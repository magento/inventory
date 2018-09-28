<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Framework\Api\DataObjectHelper;
use Magento\InventoryConfigurationApi\Api\Data\StockItemConfigurationInterface;
use Magento\InventoryConfigurationApi\Api\Data\StockItemConfigurationInterfaceFactory;
use Magento\InventoryConfigurationApi\Api\SaveStockConfigurationInterface;
use Magento\TestFramework\Helper\Bootstrap;

/** @var StockItemConfigurationInterfaceFactory $stockItemConfigurationFactory */
$stockItemConfigurationFactory = Bootstrap::getObjectManager()->get(StockItemConfigurationInterfaceFactory::class);

/** @var SaveStockConfigurationInterface $saveStockItemConfiguration */
$saveStockItemConfiguration = Bootstrap::getObjectManager()->get(SaveStockConfigurationInterface::class);

/** @var DataObjectHelper $dataObjectHelper */
$dataObjectHelper = Bootstrap::getObjectManager()->get(DataObjectHelper::class);

$stockItemsConfigurationData = [
    [
        'sku' => 'SKU-1',
        'stock_id' => 10,
        'configuration' => [
            StockItemConfigurationInterface::IS_QTY_DECIMAL => true,
            StockItemConfigurationInterface::ENABLE_QTY_INCREMENTS => false,
        ],
    ], [
        'sku' => 'SKU-1',
        'stock_id' => 20,
        'configuration' => [
            StockItemConfigurationInterface::IS_QTY_DECIMAL => true,
            StockItemConfigurationInterface::ENABLE_QTY_INCREMENTS => false,
        ],
    ], [
        'sku' => 'SKU-1',
        'stock_id' => 30,
        'configuration' => [
            StockItemConfigurationInterface::IS_QTY_DECIMAL => true,
            StockItemConfigurationInterface::ENABLE_QTY_INCREMENTS => false,
        ],
    ]
];

foreach ($stockItemsConfigurationData as $stockItemConfigurationData) {
    /** @var StockItemConfigurationInterface $stockItemConfiguration */
    $stockItemConfiguration = $stockItemConfigurationFactory->create();
    $dataObjectHelper->populateWithArray(
        $stockItemConfiguration,
        $stockItemConfigurationData['configuration'],
        StockItemConfigurationInterface::class
    );

    $saveStockItemConfiguration->forStockItem(
        $stockItemConfigurationData['sku'],
        $stockItemConfigurationData['stock_id'],
        $stockItemConfiguration
    );
}
