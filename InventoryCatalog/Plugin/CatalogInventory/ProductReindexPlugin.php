<?php
/************************************************************************
 *
 * Copyright 2024 Adobe
 * All Rights Reserved.
 *
 * NOTICE: All information contained herein is, and remains
 * the property of Adobe and its suppliers, if any. The intellectual
 * and technical concepts contained herein are proprietary to Adobe
 * and its suppliers and are protected by all applicable intellectual
 * property laws, including trade secret and copyright laws.
 * Dissemination of this information or reproduction of this material
 * is strictly forbidden unless prior written permission is obtained
 * from Adobe.
 * ************************************************************************
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Plugin\CatalogInventory;

use Magento\CatalogRule\Model\Indexer\IndexBuilder;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Exception\LocalizedException;

/**
 * Plugin to validate rule conditions using reindex after updated data in product
 */
class ProductReindexPlugin
{
    /**
     * @var IndexBuilder
     */
    private $indexBuilder;

    /**
     * @param IndexBuilder $indexBuilder
     */
    public function __construct(
        IndexBuilder $indexBuilder
    ) {
        $this->indexBuilder = $indexBuilder;
    }

    /**
     * Reindex product by id after updated data in product
     *
     * @param EventObserver $subject
     * @param callable $proceed
     * @param EventObserver $observer
     * @return void
     * @throws LocalizedException
     */
    public function aroundExecute($subject, callable $proceed, $observer)
    {
        $proceed($observer);
        $product = $observer->getEvent()->getProduct();
        if ($product->getId()) {
            //Reindex is needed to validate product with updated stock data against rule conditions
            $this->indexBuilder->reindexById($product->getId());
        }
    }
}
