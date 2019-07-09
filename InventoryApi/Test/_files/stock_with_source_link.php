<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\InventoryApi\Api\Data\StockSourceLinkInterface;
use Magento\InventoryApi\Api\Data\StockSourceLinkInterfaceFactory;
use Magento\InventoryApi\Api\StockSourceLinksSaveInterface;
use Magento\TestFramework\Helper\Bootstrap;

require __DIR__ . '/stock.php';
require __DIR__ . '/source.php';

$objectManager = Bootstrap::getObjectManager();
/** @var StockSourceLinksSaveInterface $stockSourceLinksSave */
$stockSourceLinksSave = $objectManager->get(StockSourceLinksSaveInterface::class);
/** @var StockSourceLinkInterfaceFactory $stockSourceLinkFactory */
$stockSourceLinkFactory = $objectManager->get(StockSourceLinkInterfaceFactory::class);

$link = $stockSourceLinkFactory->create(
    [
        'data' => [
            StockSourceLinkInterface::STOCK_ID => $stock->getStockId(),
            StockSourceLinkInterface::SOURCE_CODE => $source->getSourceCode(),
            StockSourceLinkInterface::PRIORITY => 1,
        ],
    ]
);

$stockSourceLinksSave->execute([$link]);
