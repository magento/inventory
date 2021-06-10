<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Indexer\SourceItem;

use Magento\InventoryCatalogApi\Api\DefaultStockProviderInterface;
use Magento\InventorySalesApi\Api\AreProductsSalableInterface;

/**
 * Get salable statuses for products from array of SourceItems
 */
class GetSalableStatuses
{
    /**
     * @var GetSkuListInStock
     */
    private $getSkuListInStock;

    /**
     * @var DefaultStockProviderInterface
     */
    private $defaultStockProvider;

    /**
     * @var AreProductsSalableInterface
     */
    private $areProductsSalable;

    /**
     * @param GetSkuListInStock $getSkuListInStockToUpdate
     * @param DefaultStockProviderInterface $defaultStockProvider
     * @param AreProductsSalableInterface $areProductsSalable
     */
    public function __construct(
        GetSkuListInStock $getSkuListInStockToUpdate,
        DefaultStockProviderInterface $defaultStockProvider,
        AreProductsSalableInterface $areProductsSalable
    ) {
        $this->getSkuListInStock = $getSkuListInStockToUpdate;
        $this->defaultStockProvider = $defaultStockProvider;
        $this->areProductsSalable = $areProductsSalable;
    }

    /**
     * Get salable statuses for products based on affected source items
     *
     * @param array $sourceItemIds
     * @return array
     */
    public function execute(array $sourceItemIds) : array
    {
        $result = [];
        $skuListInStockList = $this->getSkuListInStock->execute($sourceItemIds);
        foreach ($skuListInStockList as $skuListInStock) {
            $stockId = $skuListInStock->getStockId();
            $skuList = $skuListInStock->getSkuList();
            $salableStatusList = $this->areProductsSalable->execute($skuList, $stockId);
            foreach ($salableStatusList as $salableStatusItem) {
                $result[$salableStatusItem->getSku()] = [$stockId => $salableStatusItem->isSalable()];
            }
        }
        return $result;
    }
}
