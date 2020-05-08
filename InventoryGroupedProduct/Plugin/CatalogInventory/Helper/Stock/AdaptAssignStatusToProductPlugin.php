<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryGroupedProduct\Plugin\CatalogInventory\Helper\Stock;

use Magento\Catalog\Model\Product;
use Magento\CatalogInventory\Helper\Stock;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\GroupedProduct\Model\Product\Type\Grouped;
use Magento\InventoryGroupedProduct\Model\IsGroupedProductSalable;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\StockResolverInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Process grouped product stock status considering associated products salable status.
 */
class AdaptAssignStatusToProductPlugin
{
    /**
     * @var IsGroupedProductSalable
     */
    private $isGroupedProductSalable;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var StockResolverInterface
     */
    private $stockResolver;

    /**
     * @param IsGroupedProductSalable $isGroupedProductSalable
     * @param StoreManagerInterface $storeManager
     * @param StockResolverInterface $stockResolver
     */
    public function __construct(
        IsGroupedProductSalable $isGroupedProductSalable,
        StoreManagerInterface $storeManager,
        StockResolverInterface $stockResolver
    ) {
        $this->isGroupedProductSalable = $isGroupedProductSalable;
        $this->storeManager = $storeManager;
        $this->stockResolver = $stockResolver;
    }

    /**
     * Process grouped product stock status considering associated products salable status.
     *
     * @param Stock $subject
     * @param Product $product
     * @param int|null $status
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeAssignStatusToProduct(
        Stock $subject,
        Product $product,
        $status = null
    ): array {
        if ($product->getTypeId() !== Grouped::TYPE_CODE) {
            return [$product, $status];
        }
        $website = $this->storeManager->getWebsite();
        $stock = $this->stockResolver->execute(SalesChannelInterface::TYPE_WEBSITE, $website->getCode());
        $status = $this->isGroupedProductSalable->execute($product, $stock->getStockId());

        return [$product, (int)$status];
    }
}
