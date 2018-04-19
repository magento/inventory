<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Plugin\CatalogInventory\Helper\Stock;

use Magento\Catalog\Model\Product;
use Magento\CatalogInventory\Helper\Stock;
use Magento\Framework\Exception\LocalizedException;
use Magento\InventoryCatalog\Model\GetSalesChannelForCurrentWebsite;
use Magento\InventorySalesApi\Api\IsProductSalableInterface;

/**
 * Adapt assignStatusToProduct for multi stocks.
 */
class AdaptAssignStatusToProductPlugin
{
    /**
     * @var GetSalesChannelForCurrentWebsite
     */
    private $getSalesChannelForCurrentWebsite;

    /**
     * @var IsProductSalableInterface
     */
    private $isProductSalable;

    /**
     * @param GetSalesChannelForCurrentWebsite $getSalesChannelForCurrentWebsite
     * @param IsProductSalableInterface $isProductSalable
     */
    public function __construct(
        GetSalesChannelForCurrentWebsite $getSalesChannelForCurrentWebsite,
        IsProductSalableInterface $isProductSalable
    ) {
        $this->getSalesChannelForCurrentWebsite = $getSalesChannelForCurrentWebsite;
        $this->isProductSalable = $isProductSalable;
    }

    /**
     * @param Stock $subject
     * @param callable $proceed
     * @param Product $product
     * @param int|null $status
     * @return void
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @throws LocalizedException
     */
    public function aroundAssignStatusToProduct(
        Stock $subject,
        callable $proceed,
        Product $product,
        $status = null
    ) {
        if (null === $product->getSku()) {
            return;
        }

        if (null === $status) {
            $salesChannel = $this->getSalesChannelForCurrentWebsite->execute();
            $status = (int)$this->isProductSalable->execute($product->getSku(), $salesChannel);
        }

        $proceed($product, $status);
    }
}
