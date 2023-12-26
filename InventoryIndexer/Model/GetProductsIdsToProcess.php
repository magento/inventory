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
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public function execute(array $before, array $after, bool $forceDefaultStockProcessing = false) : array
    {
        $productSkus = array_merge(
            array_diff(array_keys($before), array_keys($after)),
            array_diff(array_keys($after), array_keys($before))
        );
        foreach ($before as $sku => $salableData) {
            if (in_array($sku, $productSkus)) {
                continue;
            }
            $afterSalableData = $after[$sku] ?? [];
            // get stock IDs from "after" that doesn't exist in "before"
            $diff = array_diff(array_keys($afterSalableData), array_keys($salableData));
            if ($diff) {
                $productSkus[] = $sku;
                continue;
            }
            foreach ($salableData as $stockId => $isSalable) {
                if (!isset($after[$sku][$stockId])
                    || $before[$sku][$stockId] !== $after[$sku][$stockId]
                    || ($stockId === $this->defaultStockProvider->getId() && $forceDefaultStockProcessing)) {
                    $productSkus[] = $sku;
                }
            }
        }

        return $this->getProductIdsBySkus($productSkus);
    }

    /**
     * Retrieve product ids by skus
     *
     * @param array $productSkus
     * @return array
     */
    private function getProductIdsBySkus(array $productSkus): array
    {
        if (empty($productSkus)) {
            return [];
        }

        $productSkus = array_unique($productSkus);
        $ids = [];
        foreach ($productSkus as $sku) {
            try {
                $ids[] = $this->getProductIdsBySkus->execute([$sku]);
            } catch (NoSuchEntityException $e) {
                continue;
            }
        }
        return array_merge(...$ids);
    }
}
