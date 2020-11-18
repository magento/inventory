<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\InventoryCatalog\Model;

use Magento\Catalog\Model\Product;
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
     * @param Product $product
     * @return bool
     */
    public function execute(Product $product): bool
    {
        if (null === $product->getSku() || !((int)$product->getStatus() === Status::STATUS_ENABLED)) {
            return false;
        }
        if ($product->hasData('is_salable')) {
            return (bool)$product->getData('is_salable');
        }
        $stockId = $this->getStockIdForCurrentWebsite->execute();
        $result = current($this->areProductsSalable->execute([$product->getData(Product::SKU)], $stockId));
        $isSalable = $result->isSalable();
        $product->setData('is_salable', $isSalable);

        return $isSalable;
    }
}
