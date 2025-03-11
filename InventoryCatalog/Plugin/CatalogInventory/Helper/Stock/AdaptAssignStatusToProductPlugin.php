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
use Magento\InventoryCatalog\Model\GetStockIdForByStoreId;
use Magento\InventoryCatalogApi\Model\GetProductIdsBySkusInterface;
use Magento\InventorySalesApi\Api\AreProductsSalableInterface;

/**
 * Adapt assignStatusToProduct for multi stocks.
 */
class AdaptAssignStatusToProductPlugin
{
    /**
     * @param AreProductsSalableInterface $areProductsSalable
     * @param GetProductIdsBySkusInterface $getProductIdsBySkus
     * @param GetStockIdForByStoreId $getStockIdForByStoreId
     */
    public function __construct(
        private readonly AreProductsSalableInterface $areProductsSalable,
        private readonly GetProductIdsBySkusInterface $getProductIdsBySkus,
        private readonly GetStockIdForByStoreId $getStockIdForByStoreId
    ) {
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
            $this->getProductIdsBySkus->execute([$product->getSku()]);
            if (null === $status) {
                $stockId = $this->getStockIdForByStoreId->execute((int) $product->getStoreId());
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
