<?php
/**
 * Copyright 2017 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

use Magento\Framework\Api\DataObjectHelper;
use Magento\InventoryApi\Api\Data\StockInterface;
use Magento\InventoryApi\Api\Data\StockInterfaceFactory;
use Magento\InventoryApi\Api\StockRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;

/** @var StockInterfaceFactory $stockFactory */
$stockFactory = Bootstrap::getObjectManager()->get(StockInterfaceFactory::class);
/** @var DataObjectHelper $dataObjectHelper */
$dataObjectHelper = Bootstrap::getObjectManager()->get(DataObjectHelper::class);
/** @var StockRepositoryInterface $stockRepository */
$stockRepository = Bootstrap::getObjectManager()->get(StockRepositoryInterface::class);

/** @var StockInterface $stock */
$stock = $stockFactory->create();
$dataObjectHelper->populateWithArray(
    $stock,
    [
        StockInterface::STOCK_ID => 10,
        StockInterface::NAME => 'stock-name-1',
    ],
    StockInterface::class
);
$stockRepository->save($stock);
