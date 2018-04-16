<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Plugin\CatalogInventory\Helper\Stock;

use Magento\Catalog\Model\Product;
use Magento\CatalogInventory\Helper\Stock;
use Magento\InventoryCatalog\Api\DefaultStockProviderInterface;
use Magento\InventoryCatalog\Model\GetStockIdForCurrentWebsite;
use Magento\InventorySales\Model\GetActualSalesChannel;
use Magento\InventorySalesApi\Api\IsProductSalableInterface;

/**
 * Adapt assignStatusToProduct for multi stocks.
 */
class AdaptAssignStatusToProductPlugin
{
    /**
     * @var GetStockIdForCurrentWebsite
     */
    private $getStockIdForCurrentWebsite;

    /**
     * @var IsProductSalableInterface
     */
    private $isProductSalable;

    /**
     * @var DefaultStockProviderInterface
     */
    private $defaultStockProvider;

    /**
     * @var GetActualSalesChannel
     */
    private $getActualSalesChannel;

    /**
     * @param GetStockIdForCurrentWebsite $getStockIdForCurrentWebsite
     * @param IsProductSalableInterface $isProductSalable
     * @param DefaultStockProviderInterface $defaultStockProvider
     */
    public function __construct(
        GetStockIdForCurrentWebsite $getStockIdForCurrentWebsite,
        IsProductSalableInterface $isProductSalable,
        DefaultStockProviderInterface $defaultStockProvider,
        GetActualSalesChannel $getActualSalesChannel
    ) {
        $this->getStockIdForCurrentWebsite = $getStockIdForCurrentWebsite;
        $this->isProductSalable = $isProductSalable;
        $this->defaultStockProvider = $defaultStockProvider;
        $this->getActualSalesChannel = $getActualSalesChannel;
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

        if (null === $status) {
            $stockId = $this->getStockIdForCurrentWebsite->execute();
            $salesChannel = $this->getActualSalesChannel->execute($stockId);
            $status = (int)$this->isProductSalable->execute($product->getSku(), $stockId, $salesChannel);
        }

        $proceed($product, $status);
    }
}
