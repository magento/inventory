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

/**
 * Get salable product status service.
 */
class IsProductSalable
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
     * @var array
     */
    private $productStatusCache;

    /**
     * @param GetStockIdForCurrentWebsite $getStockIdForCurrentWebsite
     * @param AreProductsSalableInterface $areProductsSalable
     */
    public function __construct(
        GetStockIdForCurrentWebsite $getStockIdForCurrentWebsite,
        AreProductsSalableInterface $areProductsSalable
    ) {
        $this->getStockIdForCurrentWebsite = $getStockIdForCurrentWebsite;
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
        $stockId = $this->getStockIdForCurrentWebsite->execute();
        //use getData('sku') to get non processed product sku for complex products.
        if (isset($this->productStatusCache[$stockId][$product->getData('sku')])) {
            return $this->productStatusCache[$stockId][$product->getSku()];
        }

        $stockId = $this->getStockIdForCurrentWebsite->execute();
        $result = current($this->areProductsSalable->execute([$product->getData('sku')], $stockId));
        $salabilityStatus = $result->isSalable();
        $this->productStatusCache[$stockId][$product->getData('sku')] = $salabilityStatus;

        return $salabilityStatus;
    }
}
