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
use Magento\InventoryCatalog\Model\GetStockIdForCurrentWebsite;
use Magento\InventoryCatalogApi\Api\DefaultStockProviderInterface;
use Magento\InventoryCatalogApi\Model\GetProductIdsBySkusInterface;
use Magento\InventorySalesApi\Api\AreProductsSalableInterface;
use Magento\InventoryIndexer\Model\GetStockItemData\CacheStorage as StockItemDataCacheStorage;
use Magento\CatalogInventory\Model\Stock as StockModel;

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
     * @var AreProductsSalableInterface
     */
    private $areProductsSalable;

    /**
     * @var DefaultStockProviderInterface
     */
    private $defaultStockProvider;

    /**
     * @var GetProductIdsBySkusInterface
     */
    private $getProductIdsBySkus;

    /**
     * @var StockItemDataCacheStorage
     */
    private $stockItemDataCacheStorage;

    /**
     * @param GetStockIdForCurrentWebsite $getStockIdForCurrentWebsite
     * @param AreProductsSalableInterface $areProductsSalable
     * @param DefaultStockProviderInterface $defaultStockProvider
     * @param GetProductIdsBySkusInterface $getProductIdsBySkus
     * @param StockItemDataCacheStorage $stockItemDataCacheStorage
     */
    public function __construct(
        GetStockIdForCurrentWebsite $getStockIdForCurrentWebsite,
        AreProductsSalableInterface $areProductsSalable,
        DefaultStockProviderInterface $defaultStockProvider,
        GetProductIdsBySkusInterface $getProductIdsBySkus,
        StockItemDataCacheStorage $stockItemDataCacheStorage
    ) {
        $this->getStockIdForCurrentWebsite = $getStockIdForCurrentWebsite;
        $this->areProductsSalable = $areProductsSalable;
        $this->defaultStockProvider = $defaultStockProvider;
        $this->getProductIdsBySkus = $getProductIdsBySkus;
        $this->stockItemDataCacheStorage = $stockItemDataCacheStorage;
    }

    /**
     * Assign stock status to product considering multi stock environment.
     *
     * @param Stock $subject
     * @param Product $product
     * @param int|null $status
     * @return array
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeAssignStatusToProduct(
        Stock $subject,
        Product $product,
        ?int $status = null
    ): array {
        if (null === $product->getSku()) {
            return [$product, $status];
        }

        try {
            $this->stockItemDataCacheStorage->delete(StockModel::DEFAULT_STOCK_ID, $product->getSku());
            $this->getProductIdsBySkus->execute([$product->getSku()]);
            if (null === $status) {
                $stockId = $this->getStockIdForCurrentWebsite->execute();
                $result = $this->areProductsSalable->execute([$product->getSku()], $stockId);
                $result = current($result);
                return [$product, (int)$result->isSalable()];
            }
        } catch (NoSuchEntityException $e) {
            return [$product, $status];
        }
        return [$product, $status];
    }
}
