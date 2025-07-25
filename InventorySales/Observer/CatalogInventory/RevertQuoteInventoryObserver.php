<?php
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Observer\CatalogInventory;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Catalog\Model\Indexer\Product\Price\Processor;
use Magento\CatalogInventory\Observer\ProductQty;

class RevertQuoteInventoryObserver implements ObserverInterface
{
    /**
     * @var ProductQty
     */
    private $productQty;

    /**
     * @var Processor
     */
    private $priceIndexer;

    /**
     * @param ProductQty $productQty
     * @param Processor $priceIndexer
     */
    public function __construct(
        ProductQty $productQty,
        Processor $priceIndexer
    ) {
        $this->productQty = $productQty;
        $this->priceIndexer = $priceIndexer;
    }

    /**
     * Reindexes product prices and resets the inventory processed flag for a quote to allow retrying order placement.
     *
     * @param EventObserver $observer
     * @return void
     */
    public function execute(EventObserver $observer)
    {
        $quote = $observer->getEvent()->getQuote();
        $items = $this->productQty->getProductQty($quote->getAllItems());
        $productIds = array_keys($items);
        if (!empty($productIds)) {
            $this->priceIndexer->reindexList($productIds);
        }
        // Clear flag, so if order placement retried again with success - it will be processed
        $quote->setInventoryProcessed(false);
    }
}
