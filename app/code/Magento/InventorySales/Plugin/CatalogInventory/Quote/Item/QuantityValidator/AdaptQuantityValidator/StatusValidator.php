<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Plugin\CatalogInventory\Quote\Item\QuantityValidator\AdaptQuantityValidator;

use Magento\InventoryCatalogApi\Model\GetSkusByProductIdsInterface;
use Magento\InventorySalesApi\Api\IsProductSalableInterface;
use Magento\CatalogInventory\Model\Stock;
use Magento\CatalogInventory\Helper\Data;

/**
 * Check product stock status for given quote item.
 */
class StatusValidator
{
    /**
     * @var IsProductSalableInterface
     */
    private $isProductSalable;

    /**
     * @var GetSkusByProductIdsInterface
     */
    private $getSkusByProductIds;

    /**
     * @param GetSkusByProductIdsInterface $getSkusByProductIds
     * @param IsProductSalableInterface $isProductSalable
     */
    public function __construct(
        GetSkusByProductIdsInterface $getSkusByProductIds,
        IsProductSalableInterface $isProductSalable
    ) {
        $this->isProductSalable = $isProductSalable;
        $this->getSkusByProductIds = $getSkusByProductIds;
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item $quoteItem
     * @param int $stockId
     * @param string $sku
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute($quoteItem, int $stockId, string $sku): bool
    {
        $parentStockStatus = 1;
        $result = true;
        if ($quoteItem->getParentItem()) {
            $parentProduct = $quoteItem->getParentItem()->getProduct();
            $parentSku = $this->getSkusByProductIds->execute([$parentProduct->getId()])[$parentProduct->getId()];
            $parentStockStatus = (int)$this->isProductSalable->execute($parentSku, $stockId);
        }
        $stockStatus = (int)$this->isProductSalable->execute($sku, $stockId);

        if ($stockStatus === Stock::STOCK_OUT_OF_STOCK || $parentStockStatus === Stock::STOCK_OUT_OF_STOCK) {
            $quoteItem->addErrorInfo(
                'cataloginventory',
                Data::ERROR_QTY,
                __('This product is out of stock.')
            );
            $quoteItem->getQuote()->addErrorInfo(
                'stock',
                'cataloginventory',
                Data::ERROR_QTY,
                __('Some of the products are out of stock.')
            );
            $result = false;
        }

        return $result;
    }
}
