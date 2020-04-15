<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Plugin\CatalogInventory\Helper\Stock;

use Magento\Catalog\Model\ResourceModel\Collection\AbstractCollection;
use Magento\CatalogInventory\Helper\Stock;
use Magento\InventoryCatalog\Model\GetStockIdForCurrentWebsite;
use Magento\InventorySalesApi\Api\AreProductsSalableInterface;

/**
 * Adapt addStockStatusToProducts for multi stocks.
 */
class AdaptAddStockStatusToProductsPlugin
{
    /**
     * @var GetStockIdForCurrentWebsite
     */
    private $getStockIdForCurrentWebsite;

    /**
     * @var AreProductsSalableInterface
     */
    private $areProductsSalable;

    /**
     * @param GetStockIdForCurrentWebsite $getStockIdForCurrentWebsite
     * @param AreProductsSalableInterface $areProductsSalable
     */
    public function __construct(
        GetStockIdForCurrentWebsite $getStockIdForCurrentWebsite,
        AreProductsSalableInterface $areProductsSalable
    ) {
        $this->getStockIdForCurrentWebsite = $getStockIdForCurrentWebsite;
        $this->areProductsSalable = $areProductsSalable;
    }

    /**
     * Add stock status to products considering multi stock environment.
     *
     * @param Stock $subject
     * @param callable $proceed
     * @param AbstractCollection $productCollection
     * @return void
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundAddStockStatusToProducts(
        Stock $subject,
        callable $proceed,
        AbstractCollection $productCollection
    ) {
        $stockId = $this->getStockIdForCurrentWebsite->execute();

        $skus = [];
        foreach ($productCollection as $product) {
            $skus[] = $product->getSku();
        }
        $results = $this->areProductsSalable->execute($skus, $stockId);
        foreach ($results as $result) {
            $product = $productCollection->getItemByColumnValue('sku', $result->getSku());
            $product->setIsSalable($result->isSalable());
        }
    }
}
