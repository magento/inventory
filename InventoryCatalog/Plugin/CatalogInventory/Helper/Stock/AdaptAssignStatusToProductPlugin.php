<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Plugin\CatalogInventory\Helper\Stock;

use Magento\Catalog\Model\Product;
use Magento\CatalogInventory\Helper\Stock;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryCatalogApi\Api\DefaultStockProviderInterface;
use Magento\InventoryCatalogApi\Model\GetProductIdsBySkusInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\IsProductSalableInterface;
use Magento\InventorySalesApi\Api\StockResolverInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Adapt assignStatusToProduct for multi stocks.
 */
class AdaptAssignStatusToProductPlugin
{
    /**
     * @var IsProductSalableInterface
     */
    private $isProductSalable;

    /**
     * @var DefaultStockProviderInterface
     */
    private $defaultStockProvider;

    /**
     * @var GetProductIdsBySkusInterface
     */
    private $getProductIdsBySkus;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var StockResolverInterface
     */
    private $stockResolver;

    /**
     * @param IsProductSalableInterface $isProductSalable
     * @param DefaultStockProviderInterface $defaultStockProvider
     * @param GetProductIdsBySkusInterface $getProductIdsBySkus
     * @param StoreManagerInterface $storeManager
     * @param StockResolverInterface $stockResolver
     */
    public function __construct(
        IsProductSalableInterface $isProductSalable,
        DefaultStockProviderInterface $defaultStockProvider,
        GetProductIdsBySkusInterface $getProductIdsBySkus,
        StoreManagerInterface $storeManager,
        StockResolverInterface $stockResolver
    ) {
        $this->isProductSalable = $isProductSalable;
        $this->defaultStockProvider = $defaultStockProvider;
        $this->getProductIdsBySkus = $getProductIdsBySkus;
        $this->storeManager = $storeManager;
        $this->stockResolver = $stockResolver;
    }

    /**
     * @param Stock $subject
     * @param callable $proceed
     * @param Product $product
     * @param int|null $status
     * @return void
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
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

        try {
            $this->getProductIdsBySkus->execute([$product->getSku()]);

            if (null === $status) {
                $website = $product->getStore()->getWebsite() ?: $this->storeManager->getWebsite();
                $stock = $this->stockResolver->execute(SalesChannelInterface::TYPE_WEBSITE, $website->getCode());
                $status = (int)$this->isProductSalable->execute($product->getSku(), $stock->getStockId());
            }

            $proceed($product, $status);
        } catch (NoSuchEntityException $e) {
            return;
        }
    }
}
