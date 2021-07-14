<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Model;

use Magento\InventoryCatalogApi\Model\GetProductIdsBySkusInterface;
use Magento\InventoryCatalogApi\Api\DefaultStockProviderInterface;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Compare status for products before and after reindex
 */
class GetProductsIdsToProcess
{
    /**
     * @var GetProductIdsBySkusInterface
     */
    private $getProductIdsBySkus;

    /**
     * @var DefaultStockProviderInterface
     */
    private $defaultStockProvider;

    /**
     * @param GetProductIdsBySkusInterface $getProductIdsBySkus
     * @param DefaultStockProviderInterface $defaultStockProvider
     */
    public function __construct(
        GetProductIdsBySkusInterface $getProductIdsBySkus,
        DefaultStockProviderInterface $defaultStockProvider
    ) {
        $this->getProductIdsBySkus = $getProductIdsBySkus;
        $this->defaultStockProvider = $defaultStockProvider;
    }

    /**
     * Compares state before and after reindex, filter only products with changed state
     *
     * @param array $before
     * @param array $after
     * @param bool $forceDefaultStockProcessing
     * @return array
     */
    public function execute(array $before, array $after, bool $forceDefaultStockProcessing = false) : array
    {
        $productIds = [];
        $productSkus = array_merge(
            array_diff(array_keys($before), array_keys($after)),
            array_diff(array_keys($after), array_keys($before))
        );
        foreach ($before as $sku => $salableData) {
            if (!in_array($sku, $productSkus)) {
                foreach ($salableData as $stockId => $isSalable) {
                    if (empty($after[$sku][$stockId])
                        || $before[$sku][$stockId] !== $after[$sku][$stockId]
                        || ($stockId === $this->defaultStockProvider->getId() && $forceDefaultStockProcessing)) {
                        $productSkus[] = $sku;
                    }
                }
            }
        }
        if (!empty($productSkus)) {
            $productSkus = array_unique($productSkus);
            foreach ($productSkus as $sku) {
                try {
                    $productId = $this->getProductIdsBySkus->execute([$sku]);
                    $productIds = array_merge($productIds, $productId);
                } catch (NoSuchEntityException $e) {
                    continue;
                }
            }
        }
        return $productIds;
    }
}
