<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryImportExport\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\MessageQueue\PublisherInterface;

/**
 * Clean source items after products removed during import observer.
 */
class DeleteSourceItemsAfterProductDeleteObserver implements ObserverInterface
{
    /**
     * @var PublisherInterface
     */
    private $publisher;

    /**
     * @param PublisherInterface $publisher
     */
    public function __construct(PublisherInterface $publisher)
    {
        $this->publisher = $publisher;
    }

    /**
     * Asynchronously delete source items after products have been removed during import.
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        $skus = [];
        $bunch = $observer->getEvent()->getData('bunch');
        foreach ($bunch as $product) {
            if (isset($product['sku'])) {
                $skus[] = $product['sku'];
            }
        }
        $this->publisher->publish('async.inventory.source.items.cleanup', [$skus]);
    }
}
