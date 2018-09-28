<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\InventoryConfigurationApi\Api\Data\StockItemConfigurationInterfaceFactory;
use Magento\InventoryConfigurationApi\Api\SaveStockConfigurationInterface;
use Magento\TestFramework\Helper\Bootstrap;

/** @var StockItemConfigurationInterfaceFactory $stockItemConfigurationFactory */
$stockItemConfigurationFactory = Bootstrap::getObjectManager()->get(StockItemConfigurationInterfaceFactory::class);

/** @var SaveStockConfigurationInterface $saveStockItemConfiguration */
$saveStockItemConfiguration = Bootstrap::getObjectManager()->get(SaveStockConfigurationInterface::class);

$stockItemsConfigurationData = [
    [
        'sku' => 'SKU-1',
        'stock_id' => 10,
    ], [
        'sku' => 'SKU-1',
        'stock_id' => 20,
    ], [
        'sku' => 'SKU-1',
        'stock_id' => 30,
    ]
];

foreach ($stockItemsConfigurationData as $stockItemConfigurationData) {
    $stockItemConfiguration = $stockItemConfigurationFactory->create();

    // Null fill the existing configuration
    $saveStockItemConfiguration->forStockItem(
        $stockItemConfigurationData['sku'],
        $stockItemConfigurationData['stock_id'],
        $stockItemConfiguration
    );
}
