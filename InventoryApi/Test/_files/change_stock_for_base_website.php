<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\TestFramework\Helper\Bootstrap;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterfaceFactory;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;

require __DIR__ . '/stock_with_source_link.php';

$objectManager = Bootstrap::getObjectManager();
/** @var SalesChannelInterfaceFactory $salesChannelFactory */
$salesChannelFactory = $objectManager->get(SalesChannelInterfaceFactory::class);

$extensionAttributes = $stock->getExtensionAttributes();
$salesChannel = $salesChannelFactory->create();
$salesChannel->setCode('base');
$salesChannel->setType(SalesChannelInterface::TYPE_WEBSITE);
$extensionAttributes->setSalesChannels([$salesChannel]);
$stockRepository->save($stock);
