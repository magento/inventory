<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryProductAlert\Plugin;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryApi\Api\Data\StockInterface;
use Magento\InventorySalesApi\Api\AreProductsSalableInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\StockResolverInterface;
use Magento\ProductAlert\Model\ProductSalability;
use Magento\Store\Api\Data\WebsiteInterface;

/**
 * Adapt product salability for multi source.
 */
class AdaptProductSalabilityPlugin
{
    /**
     * @var StockResolverInterface
     */
    private $stockResolver;

    /**
     * @var AreProductsSalableInterface
     */
    private $areProductsSalable;

    /**
     * @param StockResolverInterface $stockResolver
     * @param AreProductsSalableInterface $areProductsSalable
     */
    public function __construct(
        StockResolverInterface $stockResolver,
        AreProductsSalableInterface $areProductsSalable
    ) {
        $this->stockResolver = $stockResolver;
        $this->areProductsSalable = $areProductsSalable;
    }

    /**
     * Get product saleability status considering multi stock environment.
     *
     * @param ProductSalability $productSalability
     * @param callable $proceed
     * @param ProductInterface $product
     * @param WebsiteInterface $website
     * @return bool
     * @throws NoSuchEntityException
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundIsSalable(
        ProductSalability $productSalability,
        callable $proceed,
        ProductInterface $product,
        WebsiteInterface $website
    ): bool {
        /** @var StockInterface $stock */
        $stock = $this->stockResolver->execute(SalesChannelInterface::TYPE_WEBSITE, $website->getCode());
        $result = $this->areProductsSalable->execute([$product->getSku()], (int)$stock->getStockId());
        $result = current($result);

        return $result->isSalable();
    }
}
