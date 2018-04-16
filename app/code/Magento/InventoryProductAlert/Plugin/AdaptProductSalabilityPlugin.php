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
use Magento\InventorySales\Model\GetActualSalesChannel;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\IsProductSalableInterface;
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
     * @var IsProductSalableInterface
     */
    private $isProductSalable;
    
    /**
     * @var GetActualSalesChannel
     */
    private $getActualSalesChannel;

    /**
     * @param StockResolverInterface $stockResolver
     * @param IsProductSalableInterface $isProductSalable
     */
    public function __construct(
        StockResolverInterface $stockResolver,
        IsProductSalableInterface $isProductSalable,
        GetActualSalesChannel $getActualSalesChannel
    ) {
        $this->stockResolver = $stockResolver;
        $this->isProductSalable = $isProductSalable;
        $this->getActualSalesChannel = $getActualSalesChannel;
    }

    /**
     * @param  ProductSalability $productSalability
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
        $stock = $this->stockResolver->get(SalesChannelInterface::TYPE_WEBSITE, $website->getCode());
        $stockId = (int)$stock->getStockId();
        $salesChannel = $this->getActualSalesChannel->execute($stockId);

        $isSalable = $this->isProductSalable->execute($product->getSku(), $stockId, $salesChannel);

        return $isSalable;
    }
}
