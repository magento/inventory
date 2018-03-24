<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CatalogInventory\Observer;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\CatalogInventory\Helper\Stock as StockHelper;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;

class AddInventoryDataToProductCollectionObserver implements ObserverInterface
{
    /**
     * @var StockHelper
     */
    protected $stockHelper;

    /**
     * @param StockHelper $stockHelper
     */
    public function __construct(StockHelper $stockHelper)
    {
        $this->stockHelper = $stockHelper;
    }

    /**
     * Add inventory data to product collection
     *
     * @param EventObserver $observer
     *
     * @return void
     */
    public function execute(EventObserver $observer)
    {
        /** @var ProductCollection $collection */
        $collection = $observer->getData('collection');
        $products = $collection->getItems();

        if (count($products)) {
            foreach ($products as $product) {
                if ($product instanceof Product) {
                    $this->stockHelper->assignStatusToProduct($product);
                }
            }
        }
    }
}