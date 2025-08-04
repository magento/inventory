<?php
/**
 * Copyright 2017 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

use Magento\InventoryApi\Api\StockRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;

/** @var StockRepositoryInterface $stockRepository */
$stockRepository = Bootstrap::getObjectManager()->get(StockRepositoryInterface::class);

// Firstly clear relations with sales channels
$stock = $stockRepository->get(10);
$stock->getExtensionAttributes()->setSalesChannels([]);
$stockRepository->save($stock);

$stockRepository->deleteById(10);
