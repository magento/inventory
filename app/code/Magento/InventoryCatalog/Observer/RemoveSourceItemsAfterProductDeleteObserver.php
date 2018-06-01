<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\InventoryApi\Api\GetSourceItemsBySkuInterface;
use Magento\InventoryApi\Api\SourceItemsDeleteInterface;

class RemoveSourceItemsAfterProductDeleteObserver implements ObserverInterface
{
    /**
     * @var GetSourceItemsBySkuInterface
     */
    private $getSourceItemsBySku;

    /**
     * @var SourceItemsDeleteInterface
     */
    private $sourceItemsDelete;

    /**
     * @param GetSourceItemsBySkuInterface $getSourceItemsBySku
     * @param SourceItemsDeleteInterface $sourceItemsDelete
     */
    public function __construct(
        GetSourceItemsBySkuInterface $getSourceItemsBySku,
        SourceItemsDeleteInterface $sourceItemsDelete
    ) {
        $this->getSourceItemsBySku = $getSourceItemsBySku;
        $this->sourceItemsDelete = $sourceItemsDelete;
    }

    /**
     * Remove all source items related to deleted product
     *
     * @param EventObserver $observer
     * @return void
     */
    public function execute(EventObserver $observer)
    {
        $product = $observer->getEvent()->getProduct();
        if ($product && $product->getId()) {
            $sourceItems = $this->getSourceItemsBySku->execute($product->getSku());
            $this->sourceItemsDelete->execute($sourceItems);
        }
    }
}
