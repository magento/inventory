<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Plugin\CatalogInventory\Helper\Stock;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Collection\AbstractCollection;
use Magento\CatalogInventory\Helper\Stock;
use Magento\Framework\Exception\LocalizedException;
use Magento\InventoryCatalog\Model\GetSalesChannelForCurrentWebsite;
use Magento\InventorySalesApi\Api\IsProductSalableInterface;
use Magento\InventoryCatalog\Model\GetStockIdForCurrentWebsite;

/**
 * Adapt addStockStatusToProducts for multi stocks.
 */
class AdaptAddStockStatusToProductsPlugin
{
    /**
     * @var IsProductSalableInterface
     */
    private $isProductSalable;

    /**
     * @var GetSalesChannelForCurrentWebsite
     */
    private $getSalesChannelForCurrentWebsite;

    /**
     * @param GetSalesChannelForCurrentWebsite $getSalesChannelForCurrentWebsite
     * @param IsProductSalableInterface $isProductSalable
     */
    public function __construct(
        GetSalesChannelForCurrentWebsite $getSalesChannelForCurrentWebsite,
        IsProductSalableInterface $isProductSalable
    ) {
        $this->isProductSalable = $isProductSalable;
        $this->getSalesChannelForCurrentWebsite = $getSalesChannelForCurrentWebsite;
    }

    /**
     * @param Stock $subject
     * @param callable $proceed
     * @param AbstractCollection $productCollection
     * @return void
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @throws LocalizedException
     */
    public function aroundAddStockStatusToProducts(
        Stock $subject,
        callable $proceed,
        AbstractCollection $productCollection
    ) {
        $salesChannel = $this->getSalesChannelForCurrentWebsite->execute();

        /** @var Product $product */
        foreach ($productCollection as $product) {
            $isSalable = (int)$this->isProductSalable->execute($product->getSku(), $salesChannel);
            $product->setIsSalable($isSalable);
        }
    }
}
