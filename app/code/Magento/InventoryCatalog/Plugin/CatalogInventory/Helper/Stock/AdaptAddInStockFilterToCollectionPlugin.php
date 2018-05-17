<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Plugin\CatalogInventory\Helper\Stock;

use Magento\CatalogInventory\Helper\Stock;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryCatalog\Model\GetStockIdForCurrentWebsite;
use Magento\InventoryCatalog\Model\ResourceModel\AddIsInStockFieldToCollection;

/**
 * Adapt addInStockFilterToCollection for multi stocks.
 */
class AdaptAddInStockFilterToCollectionPlugin
{
    /**
     * @var GetStockIdForCurrentWebsite
     */
    private $getStockIdForCurrentWebsite;

    /**
     * @var AddIsInStockFieldToCollection
     */
    private $addIsInStockFieldToCollection;

    /**
     * @param GetStockIdForCurrentWebsite $getStockIdForCurrentWebsite
     * @param AddIsInStockFieldToCollection $addIsInStockFieldToCollection
     */
    public function __construct(
        GetStockIdForCurrentWebsite $getStockIdForCurrentWebsite,
        AddIsInStockFieldToCollection $addIsInStockFieldToCollection
    ) {
        $this->getStockIdForCurrentWebsite = $getStockIdForCurrentWebsite;
        $this->addIsInStockFieldToCollection = $addIsInStockFieldToCollection;
    }

    /**
     * @param Stock $subject
     * @param callable $proceed
     * @param $collection
     * @return void
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundAddInStockFilterToCollection(Stock $subject, callable $proceed, $collection): void
    {
        $stockId = $this->getStockIdForCurrentWebsite->execute();
        $this->addIsInStockFieldToCollection->execute($collection, $stockId);
    }
}
