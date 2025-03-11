<?php
/************************************************************************
 *
 * Copyright 2024 Adobe
 * All Rights Reserved.
 *
 * NOTICE: All information contained herein is, and remains
 * the property of Adobe and its suppliers, if any. The intellectual
 * and technical concepts contained herein are proprietary to Adobe
 * and its suppliers and are protected by all applicable intellectual
 * property laws, including trade secret and copyright laws.
 * Dissemination of this information or reproduction of this material
 * is strictly forbidden unless prior written permission is obtained
 * from Adobe.
 * ************************************************************************
 */
declare(strict_types=1);

namespace Magento\InventoryConfiguration\Model;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryCatalogApi\Model\GetProductIdsBySkusInterface;
use Magento\InventoryConfiguration\Model\LegacyStockItem\CacheStorage;

class GetLegacyStockItemsCache implements GetLegacyStockItemsInterface
{
    /**
     * @param GetLegacyStockItems $getLegacyStockItems
     * @param GetProductIdsBySkusInterface $getProductIdsBySkus
     * @param CacheStorage $cacheStorage
     */
    public function __construct(
        private readonly GetLegacyStockItems $getLegacyStockItems,
        private readonly GetProductIdsBySkusInterface $getProductIdsBySkus,
        private readonly CacheStorage $cacheStorage
    ) {
    }

    /**
     * @inheritdoc
     */
    public function execute(array $skus): array
    {
        $stockItems = [];
        $skusToLoad = [];
        foreach ($skus as $sku) {
            $stockItem = $this->cacheStorage->get($sku);
            if ($stockItem) {
                $stockItems[] = $stockItem;
            } else {
                $skusToLoad[] = $sku;
            }
        }
        if (!empty($skusToLoad)) {
            try {
                $productIds = $this->getProductIdsBySkus->execute($skusToLoad);
            } catch (NoSuchEntityException $skuNotFoundInCatalog) {
                return [];
            }
            $skusById = array_flip($productIds);
            foreach ($this->getLegacyStockItems->execute($skusToLoad) as $stockItem) {
                if (isset($skusById[$stockItem->getProductId()])) {
                    $sku = (string) $skusById[$stockItem->getProductId()];
                    $this->cacheStorage->set($sku, $stockItem);
                }
                $stockItems[] = $stockItem;
            }
        }
        return $stockItems;
    }
}
