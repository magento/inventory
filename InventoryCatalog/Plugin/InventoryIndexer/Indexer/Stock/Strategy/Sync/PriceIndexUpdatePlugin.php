<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Plugin\InventoryIndexer\Indexer\Stock\Strategy\Sync;

use Magento\Catalog\Model\Indexer\Product\Price\Processor;
use Magento\InventoryIndexer\Indexer\Stock\Strategy\Sync;
use Magento\InventoryIndexer\Model\ResourceModel\GetProductIdsByStockIds;

/**
 * Update prices for specific products after non default stock reindex.
 */
class PriceIndexUpdatePlugin
{
    /**
     * @var GetProductIdsByStockIds
     */
    private $getProductIdsByStockIds;

    /**
     * @var Processor
     */
    private $priceIndexProcessor;

    /**
     * @param Processor $priceIndexProcessor
     * @param GetProductIdsByStockIds $getProductIdsForCacheFlush
     */
    public function __construct(
        Processor $priceIndexProcessor,
        GetProductIdsByStockIds $getProductIdsForCacheFlush
    ) {
        $this->getProductIdsByStockIds = $getProductIdsForCacheFlush;
        $this->priceIndexProcessor = $priceIndexProcessor;
    }

    /**
     * Update prices after non default stock reindex.
     *
     * @param Sync $subject
     * @param void $result
     * @param array $stockIds
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterExecuteList(Sync $subject, $result, array $stockIds)
    {
        $productIds = $this->getProductIdsByStockIds->execute($stockIds);
        if (!empty($productIds)) {
            $this->priceIndexProcessor->reindexList($productIds);
        }
    }
}
