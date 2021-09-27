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
use Magento\InventoryCatalog\Model\ResourceModel\SetDataToLegacyStockItem;
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

$sourcesItemsData = [
    [
        SourceItemInterface::SOURCE_CODE => $defaultSourceProvider->getCode(),
        SourceItemInterface::SKU => 'SKU-1',
        SourceItemInterface::QUANTITY => 10,
        SourceItemInterface::STATUS => SourceItemInterface::STATUS_IN_STOCK,
    ],
    [
        SourceItemInterface::SOURCE_CODE => $defaultSourceProvider->getCode(),
        SourceItemInterface::SKU => 'SKU-2',
        SourceItemInterface::QUANTITY => 20,
        SourceItemInterface::STATUS => SourceItemInterface::STATUS_IN_STOCK,
    ],
    [
        SourceItemInterface::SOURCE_CODE => $defaultSourceProvider->getCode(),
        SourceItemInterface::SKU => 'SKU-3',
        SourceItemInterface::QUANTITY => 30,
        SourceItemInterface::STATUS => SourceItemInterface::STATUS_IN_STOCK,
    ]
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

/** @var AreProductsSalableInterface $areProductsSalable */
$areProductsSalable = Bootstrap::getObjectManager()->get(AreProductsSalableInterface::class);
/** @var SetDataToLegacyStockStatus $setDataToLegacyStockStatus */
$setDataToLegacyStockStatus = Bootstrap::getObjectManager()->get(SetDataToLegacyStockStatus::class);
/** @var SetDataToLegacyStockItem $setDataToLegacyStockItem */
$setDataToLegacyStockItem = Bootstrap::getObjectManager()->get(SetDataToLegacyStockItem::class);

$stockStatuses = [];
foreach ($areProductsSalable->execute($skus, Stock::DEFAULT_STOCK_ID) as $productSalable) {
    $stockStatuses[$productSalable->getSku()] = $productSalable->isSalable();
}
foreach ($sourceItems as $sourceItem) {
    $setDataToLegacyStockItem->execute(
        (string)$sourceItem->getSku(),
        (float)$sourceItem->getQuantity(),
        (int)$sourceItem->getStatus()
    );
    $setDataToLegacyStockStatus->execute(
        (string)$sourceItem->getSku(),
        (float)$sourceItem->getQuantity(),
        $stockStatuses[(string)$sourceItem->getSku()] === true ? 1 : 0
    );
}
