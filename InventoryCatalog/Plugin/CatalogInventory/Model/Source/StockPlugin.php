<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Plugin\CatalogInventory\Model\Source;

use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\CatalogInventory\Model\Source\Stock;
use Magento\InventoryCatalog\Model\ResourceModel\AddSortByStockQtyToCollection;
use Magento\InventoryCatalog\Model\ResourceModel\StockStatusFilter;
use Magento\InventoryCatalogApi\Api\DefaultStockProviderInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\StockResolverInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Modify "sort by low/high stock" to support non-default stocks
 */
class StockPlugin
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var StockResolverInterface
     */
    private $stockResolver;

    /**
     * @var DefaultStockProviderInterface
     */
    private $defaultStockProvider;

    /**
     * @var AddSortByStockQtyToCollection
     */
    private $addSortByStockQtyToCollection;

    /**
     * @param StoreManagerInterface $storeManager
     * @param StockResolverInterface $stockResolver
     * @param DefaultStockProviderInterface $defaultStockProvider
     * @param StockStatusFilter $stockStatusFilter
     * @param AddSortByStockQtyToCollection $addSortByStockQtyToCollection
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        StockResolverInterface $stockResolver,
        DefaultStockProviderInterface $defaultStockProvider,
        AddSortByStockQtyToCollection $addSortByStockQtyToCollection
    ) {
        $this->storeManager = $storeManager;
        $this->stockResolver = $stockResolver;
        $this->defaultStockProvider = $defaultStockProvider;
        $this->addSortByStockQtyToCollection = $addSortByStockQtyToCollection;
    }

    /**
     * Modify "sort by low/high stock" to support non-default stocks.
     *
     * @param Stock $subject
     * @param callable $proceed
     * @param mixed $collection
     * @param string $dir
     * @return Stock
     */
    public function aroundAddValueSortToCollection(
        Stock $subject,
        callable $proceed,
        Collection $collection,
        string $dir
    ): Stock {
        if ($collection->getStoreId() !== Store::DEFAULT_STORE_ID) {
            $websiteId = $this->storeManager->getStore($collection->getStoreId())->getWebsiteId();
            $websiteCode = $this->storeManager->getWebsite($websiteId)->getCode();
            $stock = $this->stockResolver->execute(SalesChannelInterface::TYPE_WEBSITE, $websiteCode);
            $stockId = (int) $stock->getStockId();
            if ($this->defaultStockProvider->getId() !== $stockId) {
                $this->addSortByStockQtyToCollection->execute($collection, $dir, $stockId);
                return $subject;
            }
        }

        return $proceed($collection, $dir);
    }
}
