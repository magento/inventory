<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Framework\Api\DataObjectHelper;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterfaceFactory;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();
/** @var DataObjectHelper $dataObjectHelper */
$dataObjectHelper =$objectManager->get(DataObjectHelper::class);
/** @var SourceItemInterfaceFactory $sourceItemFactory */
$sourceItemFactory = $objectManager->get(SourceItemInterfaceFactory::class);
/** @var  SourceItemsSaveInterface $sourceItemsSave */
$sourceItemsSave = $objectManager->get(SourceItemsSaveInterface::class);

/**
 * 0123 - EU-source-1(id:10) - 5qty
 * 1234 - EU-source-1(id:10) - 5qty
 */
$sourcesItemsData = [
    [
        SourceItemInterface::SOURCE_CODE => "eu-1",
        SourceItemInterface::SKU => "01234",
        SourceItemInterface::QUANTITY => 5,
        SourceItemInterface::STATUS => SourceItemInterface::STATUS_IN_STOCK,
    ],
    [
        SourceItemInterface::SOURCE_CODE => "eu-1",
        SourceItemInterface::SKU => "1234",
        SourceItemInterface::QUANTITY => 5,
        SourceItemInterface::STATUS => SourceItemInterface::STATUS_IN_STOCK,
    ],
];

$sourceItems = [];
foreach ($sourcesItemsData as $sourceItemData) {
    /** @var SourceItemInterface $source */
    $sourceItem = $sourceItemFactory->create();
    $dataObjectHelper->populateWithArray($sourceItem, $sourceItemData, SourceItemInterface::class);
    $sourceItems[] = $sourceItem;
}
$sourceItemsSave->execute($sourceItems);
