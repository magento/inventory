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
use Magento\InventoryCatalogApi\Api\DefaultSourceProviderInterface;
use Magento\TestFramework\Helper\Bootstrap;

$dataObjectHelper = Bootstrap::getObjectManager()->get(DataObjectHelper::class);
$sourceItemFactory = Bootstrap::getObjectManager()->get(SourceItemInterfaceFactory::class);
$sourceItemsSave = Bootstrap::getObjectManager()->get(SourceItemsSaveInterface::class);
$defaultSourceProvider = Bootstrap::getObjectManager()->get(DefaultSourceProviderInterface::class);

$sourceItemData =     [
    SourceItemInterface::SOURCE_CODE => $defaultSourceProvider->getCode(),
    SourceItemInterface::SKU => 'simple_product_bundle_option',
    SourceItemInterface::QUANTITY => 22,
    SourceItemInterface::STATUS => SourceItemInterface::STATUS_IN_STOCK,
];
$sourceItems = [];
$sourceItem = $sourceItemFactory->create();
$dataObjectHelper->populateWithArray($sourceItem, $sourceItemData, SourceItemInterface::class);
$sourceItems[] = $sourceItem;
$sourceItemsSave->execute($sourceItems);
