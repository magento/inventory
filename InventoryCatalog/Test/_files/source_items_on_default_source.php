<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\CatalogInventory\Model\Stock;
use Magento\Framework\Api\DataObjectHelper;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterfaceFactory;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;
use Magento\InventoryCatalog\Model\ResourceModel\SetDataToLegacyStockStatus;
use Magento\InventoryCatalogApi\Api\DefaultSourceProviderInterface;
use Magento\InventorySalesApi\Api\AreProductsSalableInterface;
use Magento\TestFramework\Helper\Bootstrap;

/** @var DataObjectHelper $dataObjectHelper */
$dataObjectHelper = Bootstrap::getObjectManager()->get(DataObjectHelper::class);
/** @var SourceItemInterfaceFactory $sourceItemFactory */
$sourceItemFactory = Bootstrap::getObjectManager()->get(SourceItemInterfaceFactory::class);
/** @var  SourceItemsSaveInterface $sourceItemsSave */
$sourceItemsSave = Bootstrap::getObjectManager()->get(SourceItemsSaveInterface::class);
/** @var DefaultSourceProviderInterface $defaultSourceProvider */
$defaultSourceProvider = Bootstrap::getObjectManager()->get(DefaultSourceProviderInterface::class);
/** @var AreProductsSalableInterface $areProductsSalable */
$areProductsSalable = Bootstrap::getObjectManager()->get(AreProductsSalableInterface::class);
/** @var SetDataToLegacyStockStatus $setDataToLegacyStockStatus */
$setDataToLegacyStockStatus = Bootstrap::getObjectManager()->get(SetDataToLegacyStockStatus::class);

/**
 * SKU-1 - Default-source-1(id:10) - 5.5qty
 * SKU-2 - Default-source-1(id:30) - 5qty
 * SKU-3 - Default-source-2(id:20) - 6qty (out of stock)
 */
$sourcesItemsData = [
    [
        SourceItemInterface::SOURCE_CODE => $defaultSourceProvider->getCode(),
        SourceItemInterface::SKU => 'SKU-1',
        SourceItemInterface::QUANTITY => 5.5,
        SourceItemInterface::STATUS => SourceItemInterface::STATUS_IN_STOCK,
    ],
    [
        SourceItemInterface::SOURCE_CODE => $defaultSourceProvider->getCode(),
        SourceItemInterface::SKU => 'SKU-2',
        SourceItemInterface::QUANTITY => 5,
        SourceItemInterface::STATUS => SourceItemInterface::STATUS_IN_STOCK,
    ],
    [
        SourceItemInterface::SOURCE_CODE => $defaultSourceProvider->getCode(),
        SourceItemInterface::SKU => 'SKU-3',
        SourceItemInterface::QUANTITY => 6,
        SourceItemInterface::STATUS => SourceItemInterface::STATUS_OUT_OF_STOCK,
    ],
    [
        SourceItemInterface::SOURCE_CODE => $defaultSourceProvider->getCode(),
        SourceItemInterface::SKU => 'SKU-4',
        SourceItemInterface::QUANTITY => 0,
        SourceItemInterface::STATUS => SourceItemInterface::STATUS_OUT_OF_STOCK,
    ],
    [
        SourceItemInterface::SOURCE_CODE => $defaultSourceProvider->getCode(),
        SourceItemInterface::SKU => 'SKU-6',
        SourceItemInterface::QUANTITY => 0,
        SourceItemInterface::STATUS => SourceItemInterface::STATUS_IN_STOCK,
    ],
];

$sourceItems = [];
$skus = [];
foreach ($sourcesItemsData as $sourceItemData) {
    /** @var SourceItemInterface $source */
    $sourceItem = $sourceItemFactory->create();
    $dataObjectHelper->populateWithArray($sourceItem, $sourceItemData, SourceItemInterface::class);
    $sourceItems[] = $sourceItem;
    $skus[] = $sourceItemData[SourceItemInterface::SKU];
}
$sourceItemsSave->execute($sourceItems);

$stockStatuses = [];
foreach ($areProductsSalable->execute($skus, Stock::DEFAULT_STOCK_ID) as $productSalable) {
    $stockStatuses[$productSalable->getSku()] = $productSalable->isSalable();
}

foreach ($sourceItems as $sourceItem) {
    $setDataToLegacyStockStatus->execute(
        (string)$sourceItem->getSku(),
        (float)$sourceItem->getQuantity(),
        $stockStatuses[(string)$sourceItem->getSku()] === true ? 1 : 0
    );
}
