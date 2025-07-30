<?php
/**
 * Copyright 2021 Adobe
 * All Rights Reserved.
 */
use Magento\InventoryApi\Api\StockRepositoryInterface;
use Magento\InventoryIndexer\Indexer\Stock\ReservationsIndexTable;
use Magento\TestFramework\Helper\Bootstrap;

/** @var ReservationsIndexTable $reservationsIndexTable */
$reservationsIndexTable = Bootstrap::getObjectManager()->get(ReservationsIndexTable::class);

/** @var StockRepositoryInterface $stockRepository */
$stockRepository = Bootstrap::getObjectManager()->get(StockRepositoryInterface::class);

foreach ($stockRepository->getList()->getItems() as $stock) {
    $reservationsIndexTable->dropTable($stock->getStockId());
}
