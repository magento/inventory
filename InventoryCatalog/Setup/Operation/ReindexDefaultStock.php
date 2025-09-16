<?php
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Setup\Operation;

use Magento\InventoryIndexer\Indexer\Stock\StockIndexer;
use Magento\InventoryCatalogApi\Api\DefaultStockProviderInterface;

/**
 * CReindex default stock during installation
 */
class ReindexDefaultStock
{
    /**
     * @var DefaultStockProviderInterface
     */
    private $defaultStockProvider;

    /**
     * @var StockIndexer
     */
    private $stockIndexer;

    /**
     * @param DefaultStockProviderInterface $defaultStockProvider
     * @param StockIndexer $stockIndexer
     */
    public function __construct(
        DefaultStockProviderInterface $defaultStockProvider,
        StockIndexer $stockIndexer
    ) {
        $this->defaultStockProvider = $defaultStockProvider;
        $this->stockIndexer = $stockIndexer;
    }

    /**
     * Create default stock
     *
     * @return void
     * @codingStandardsIgnoreStart
     */
    public function execute()
    {
        //$this->stockIndexer->executeRow($this->defaultStockProvider->getId());
        //@codingStandardsIgnoreEnd
    }
}
