<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Plugin;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product\IsProductSalable;
use Magento\InventoryApi\Api\Data\StockInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\IsProductSalableInterface;
use Magento\InventorySalesApi\Api\StockResolverInterface;
use Magento\Store\Api\Data\WebsiteInterface;

/**
 * Adapt is product salable for multi stock.
 */
class AdaptIsProductSalable
{
    /**
     * @var StockResolverInterface
     */
    private $stockResolver;

    /**
     * @var IsProductSalable
     */
    private $isProductSalable;

    /**
     * @param StockResolverInterface $stockResolver
     * @param IsProductSalableInterface $isProductSalable
     */
    public function __construct(
        StockResolverInterface $stockResolver,
        IsProductSalableInterface $isProductSalable
    ) {
        $this->stockResolver = $stockResolver;
        $this->isProductSalable = $isProductSalable;
    }

    /**
     * @param IsProductSalable $isProductSalable
     * @param callable $proceed
     * @param ProductInterface $product
     * @param WebsiteInterface $website
     *
     * @return bool
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundExecute(
        IsProductSalable $isProductSalable,
        callable $proceed,
        ProductInterface $product,
        WebsiteInterface $website
    ): bool {
        /** @var StockInterface $stock */
        $stock = $this->stockResolver->get(SalesChannelInterface::TYPE_WEBSITE, $website->getCode());
        $isSalable = $this->isProductSalable->execute($product->getSku(), (int)$stock->getStockId());

        return $isSalable;
    }
}
