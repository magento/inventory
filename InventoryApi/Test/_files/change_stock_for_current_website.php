<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\TestFramework\Helper\Bootstrap;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterfaceFactory;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\Store\Model\StoreManagerInterface;

require __DIR__ . '/stock_with_source_link.php';

$objectManager = Bootstrap::getObjectManager();
/** @var SalesChannelInterfaceFactory $salesChannelFactory */
$salesChannelFactory = $objectManager->get(SalesChannelInterfaceFactory::class);
/** @var StoreManagerInterface $storeManager */
$storeManager = $objectManager->get(StoreManagerInterface::class);

$extensionAttributes = $stock->getExtensionAttributes();
$salesChannel = $salesChannelFactory->create();
$website = $storeManager->getWebsite();
$salesChannel->setCode($website->getCode());
$salesChannel->setType(SalesChannelInterface::TYPE_WEBSITE);
$extensionAttributes->setSalesChannels([$salesChannel]);
$stockRepository->save($stock);
