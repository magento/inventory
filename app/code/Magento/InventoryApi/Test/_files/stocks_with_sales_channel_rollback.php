<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\InventoryApi\Api\StockRepositoryInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterfaceFactory;
use Magento\TestFramework\Helper\Bootstrap;

/** @var StockRepositoryInterface $stockRepository */
$stockRepository = Bootstrap::getObjectManager()->get(StockRepositoryInterface::class);
/** @var SalesChannelInterfaceFactory $salesChannelFactory */
$salesChannelFactory = Bootstrap::getObjectManager()->get(SalesChannelInterfaceFactory::class);

$defaultStock = $stockRepository->get(1);
$extensionAttributes = $defaultStock->getExtensionAttributes();
$salesChannels = $extensionAttributes->getSalesChannels();

// reassign on Default Stock because website can't exists without link to any Stock
/** @var SalesChannelInterface $salesChannel */
$salesChannel = $salesChannelFactory->create();
$salesChannel->setType(SalesChannelInterface::TYPE_WEBSITE);
$salesChannel->setCode('base');
$salesChannels[] = $salesChannel;

$extensionAttributes->setSalesChannels($salesChannels);
$stockRepository->save($defaultStock);

require 'stocks_rollback.php';
