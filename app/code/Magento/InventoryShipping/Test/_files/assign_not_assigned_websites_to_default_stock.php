<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\TestFramework\Helper\Bootstrap;
use Magento\Framework\App\ResourceConnection;
use Magento\InventoryApi\Api\StockRepositoryInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterfaceFactory;
use Magento\InventoryCatalogApi\Api\DefaultStockProviderInterface;

$objectManager = Bootstrap::getObjectManager();
/** @var ResourceConnection $resource */
$resource = $objectManager->get(ResourceConnection::class);
$select = $resource->getConnection()->select();
$select->from($resource->getTableName('store_website AS sw'), ['sw.code']);
$select->joinLeft(
    ['issc' => $resource->getTableName('inventory_stock_sales_channel')],
    "issc.code = sw.code AND issc.type = 'website'",
    []
);
$select->where("sw.code != 'admin' AND issc.stock_id IS NULL");
$notAssignedWebsiteCodes = $resource->getConnection()->fetchAll($select);
/** @var StockRepositoryInterface $stockRepository */
$stockRepository = $objectManager->get(StockRepositoryInterface::class);
/** @var SalesChannelInterfaceFactory $salesChannelFactory */
$salesChannelFactory = $objectManager->get(SalesChannelInterfaceFactory::class);
/** @var DefaultStockProviderInterface $defaultStockProvider */
$defaultStockProvider = $objectManager->get(DefaultStockProviderInterface::class);
$stock = $stockRepository->get($defaultStockProvider->getId());
$extensionAttributes = $stock->getExtensionAttributes();
$salesChannels = $extensionAttributes->getSalesChannels();

foreach ($notAssignedWebsiteCodes as $notAssignedWebsiteCode) {
    /** @var SalesChannelInterface $salesChannel */
    $salesChannel = $salesChannelFactory->create();
    $salesChannel->setCode($notAssignedWebsiteCode['code']);
    $salesChannel->setType(SalesChannelInterface::TYPE_WEBSITE);
    $salesChannels[] = $salesChannel;
}

$extensionAttributes->setSalesChannels($salesChannels);
$stockRepository->save($stock);
