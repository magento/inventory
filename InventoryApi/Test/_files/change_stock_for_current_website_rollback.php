<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\TestFramework\Helper\Bootstrap;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterfaceFactory;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventoryApi\Api\StockRepositoryInterface;
use Magento\InventoryCatalogApi\Api\DefaultStockProviderInterface;

$objectManager = Bootstrap::getObjectManager();
/** @var SalesChannelInterfaceFactory $salesChannelFactory */
$salesChannelFactory = $objectManager->get(SalesChannelInterfaceFactory::class);
/** @var DefaultStockProviderInterface $defaultStockProvider */
$defaultStockProvider = $objectManager->get(DefaultStockProviderInterface::class);
/** @var StockRepositoryInterface $stockRepository */
$stockRepository = $objectManager->get(StockRepositoryInterface::class);

/**
 * Change back default stock to use current website.
 */
$defaultStock = $stockRepository->get($defaultStockProvider->getId());
$extensionAttributes = $defaultStock->getExtensionAttributes();
$salesChannel = $salesChannelFactory->create();
$salesChannel->setCode('base');
$salesChannel->setType(SalesChannelInterface::TYPE_WEBSITE);
$extensionAttributes->setSalesChannels([$salesChannel]);
$stockRepository->save($defaultStock);

require __DIR__ . '/stock_with_source_link_rollback.php';
