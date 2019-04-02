<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

require 'stocks.php';

use Magento\InventoryApi\Api\StockRepositoryInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Framework\App\State;
use Magento\Framework\App\Area;

/** @var \Magento\Framework\App\ObjectManager */
$objectManager = \Magento\Framework\App\ObjectManager::getInstance();

/** @var  SalesChannelInterface */
$salesChannel = Bootstrap::getObjectManager()->get(SalesChannelInterface::class);

/** @var  StockRepositoryInterface */
$stockRepository = Bootstrap::getObjectManager()->get(StockRepositoryInterface::class);

/** @var State */
$appState = Bootstrap::getObjectManager()->get(State::class);

/** SalesChannelInterface */
$salesChannel->setCode('base');
$salesChannel->setType(SalesChannelInterface::TYPE_WEBSITE);
$salesChannels[] = $salesChannel;

//assign salesChannel to stock
/** @var StockRepositoryInterface $stockRepository */
$stock = $stockRepository->get(20);
$extensionAttributes = $stock->getExtensionAttributes();
$extensionAttributes->setSalesChannels($salesChannels);
$stock->setExtensionAttributes($extensionAttributes);
$stockRepository->save($stock);

$appState->setAreaCode(Area::AREA_FRONTEND);
