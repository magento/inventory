<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Observer;

use Magento\Catalog\Model\Indexer\Product\Category\Processor;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 *  ReindexList indexer for source-items event observer
 */
class ReindexCatalogProduct implements ObserverInterface
{
    /**
     * @var Processor
     */
    private $indexerProcessor;

    /**
     * @param Processor $indexerProcessor
     */
    public function __construct(
        Processor $indexerProcessor
    ) {
        $this->indexerProcessor = $indexerProcessor;
    }

    /**
     * ReindexList for catalog_product_category indexer
     *
     * @param Observer $observer
     *
     * @return void
     */
    public function execute(Observer $observer) : void
    {
        $ids = $observer->getData('entity_ids');
        $this->indexerProcessor->reindexList($ids);
    }
}
