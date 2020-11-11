<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\InventoryCatalog\Model;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\InventoryCatalog\Model\IsProductSalable\IsProductSalableDataStorage;
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
     * @var StockByWebsiteIdResolverInterface
     */
    private $stockByWebsiteIdResolver;

    /**
     * @var IsProductSalableDataStorage
     */
    private $isProductSalableDataStorage;

    /**
     * @param StockByWebsiteIdResolverInterface $stockByWebsiteIdResolver
     * @param AreProductsSalableInterface $areProductsSalable
     * @param IsProductSalableDataStorage $isProductSalableDataStorage
     */
    public function __construct(
        StockByWebsiteIdResolverInterface $stockByWebsiteIdResolver,
        AreProductsSalableInterface $areProductsSalable,
        IsProductSalableDataStorage $isProductSalableDataStorage
    ) {
        $this->stockByWebsiteIdResolver = $stockByWebsiteIdResolver;
        $this->areProductsSalable = $areProductsSalable;
        $this->isProductSalableDataStorage = $isProductSalableDataStorage;
    }

    /**
     * Verify product salable status.
     *
     * @param ProductInterface $product
     * @return bool
     */
    public function execute(ProductInterface $product): bool
    {
        if (null === $product->getSku() ||
            (null !== $product->getStatus() && (int)$product->getStatus() !== Status::STATUS_ENABLED)) {
            return false;
        }
        if ($product->getData('is_salable') !== null) {
            return (bool)$product->getData('is_salable');
        }
        $websiteId = (int)$product->getStore()->getWebsite()->getId();
        $stockId = $this->stockByWebsiteIdResolver->execute($websiteId)->getStockId();
        //use getData('sku') to get non processed product sku for complex products.
        if (null !== $this->isProductSalableDataStorage->getIsSalable($product->getData('sku'), $stockId)) {
            return $this->isProductSalableDataStorage->getIsSalable($product->getData('sku'), $stockId);
        }

        $result = current($this->areProductsSalable->execute([$product->getData('sku')], $stockId));
        $salabilityStatus = $result->isSalable();
        $this->isProductSalableDataStorage->setIsSalable($product->getData('sku'), $stockId, $salabilityStatus);

        return $salabilityStatus;
    }
}
