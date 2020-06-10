<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\InventoryCatalog\Model;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\InventorySalesApi\Api\AreProductsSalableInterface;
use Magento\InventorySalesApi\Model\StockByWebsiteIdResolverInterface;

/**
 * Get salable product status service.
 */
class IsProductSalable
{
    /**
     * @var AreProductsSalableInterface
     */
    private $areProductsSalable;

    /**
     * @var array
     */
    private $productStatusCache;

    /**
     * @var StockByWebsiteIdResolverInterface
     */
    private $stockByWebsiteIdResolver;

    /**
     * @param StockByWebsiteIdResolverInterface $stockByWebsiteIdResolver
     * @param AreProductsSalableInterface $areProductsSalable
     */
    public function __construct(
        StockByWebsiteIdResolverInterface $stockByWebsiteIdResolver,
        AreProductsSalableInterface $areProductsSalable
    ) {
        $this->stockByWebsiteIdResolver = $stockByWebsiteIdResolver;
        $this->areProductsSalable = $areProductsSalable;
    }

    /**
     * Verify product salable status.
     *
     * @param ProductInterface $product
     * @return bool
     */
    public function execute(ProductInterface $product): bool
    {
        if (null === $product->getSku() || (int)$product->getStatus() === Status::STATUS_DISABLED) {
            return false;
        }
        if ($product->getData('is_salable') !== null) {
            return (bool)$product->getData('is_salable');
        }
        $websiteId = (int)$product->getStore()->getWebsite()->getId();
        $stockId = $this->stockByWebsiteIdResolver->execute($websiteId)->getStockId();
        //use getData('sku') to get non processed product sku for complex products.
        if (isset($this->productStatusCache[$stockId][$product->getData('sku')])) {
            return $this->productStatusCache[$stockId][$product->getSku()];
        }

        $result = current($this->areProductsSalable->execute([$product->getData('sku')], $stockId));
        $salabilityStatus = $result->isSalable();
        $this->productStatusCache[$stockId][$product->getData('sku')] = $salabilityStatus;

        return $salabilityStatus;
    }
}
