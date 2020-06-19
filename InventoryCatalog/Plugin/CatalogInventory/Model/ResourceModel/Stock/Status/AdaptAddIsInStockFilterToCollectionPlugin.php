<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Plugin\CatalogInventory\Model\ResourceModel\Stock\Status;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status as ProductStatus;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\CatalogInventory\Model\ResourceModel\Stock\Status;
use Magento\InventoryCatalog\Model\GetStockIdForCurrentWebsite;
use Magento\InventoryCatalog\Model\ResourceModel\AddIsInStockFilterToCollection;

/**
 * Adapt adding is in stock filter to collection for multi stocks.
 */
class AdaptAddIsInStockFilterToCollectionPlugin
{
    /**
     * @var GetStockIdForCurrentWebsite
     */
    private $getStockIdForCurrentWebsite;

    /**
     * @var AddIsInStockFilterToCollection
     */
    private $addIsInStockFilterToCollection;

    /**
     * @param GetStockIdForCurrentWebsite $getStockIdForCurrentWebsite
     * @param AddIsInStockFilterToCollection $addIsInStockFilterToCollection
     */
    public function __construct(
        GetStockIdForCurrentWebsite $getStockIdForCurrentWebsite,
        AddIsInStockFilterToCollection $addIsInStockFilterToCollection
    ) {
        $this->getStockIdForCurrentWebsite = $getStockIdForCurrentWebsite;
        $this->addIsInStockFilterToCollection = $addIsInStockFilterToCollection;
    }

    /**
     * Adapt adding is in stock filter to collection for multi stocks.
     *
     * @param Status $stockStatus
     * @param callable $proceed
     * @param Collection $collection
     * @return Status
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundAddIsInStockFilterToCollection(
        Status $stockStatus,
        callable $proceed,
        $collection
    ) {
        $collection->addAttributeToFilter(ProductInterface::STATUS, ProductStatus::STATUS_ENABLED);
        $stockId = $this->getStockIdForCurrentWebsite->execute();
        $this->addIsInStockFilterToCollection->execute($collection, $stockId);

        return $stockStatus;
    }
}
