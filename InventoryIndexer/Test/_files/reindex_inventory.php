<?php
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

use Magento\Framework\Indexer\IndexerRegistry;
use Magento\InventoryIndexer\Indexer\InventoryIndexer;
use Magento\TestFramework\Helper\Bootstrap;

/** @var IndexerRegistry $indexerRegistry */
$indexerRegistry = Bootstrap::getObjectManager()->get(IndexerRegistry::class);
$indexer = $indexerRegistry->get(InventoryIndexer::INDEXER_ID);
$indexer->reindexAll();
