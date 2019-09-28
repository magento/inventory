<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\InventorySales\Observer\Product;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\IsProductSalableInterface;
use Magento\InventorySalesApi\Api\StockResolverInterface;

/**
 * Customize isSalable method of the Product model to enable it working with multiple stocks
 */
class IsProductSalable implements ObserverInterface
{
    /**
     * @var IsProductSalableInterface
     */
    private $isProductSalable;

    /**
     * @var StockResolverInterface
     */
    private $stockResolver;

    /**
     * @param IsProductSalableInterface $isProductSalable
     * @param StockResolverInterface $stockResolver
     */
    public function __construct(
        IsProductSalableInterface $isProductSalable,
        StockResolverInterface $stockResolver
    ) {
        $this->isProductSalable = $isProductSalable;
        $this->stockResolver = $stockResolver;
    }

    /**
     * Customize isSalable method of the Product model to enable it working with multiple stocks
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        /** @var \Magento\Catalog\Model\Product $product */
        $product = $observer->getProduct();
        /** @var \Magento\Framework\DataObject $salableObject */
        $salableObject = $observer->getSalable();
        if (!$salableObject->getIsSalable()) {
            return;
        }

        $websiteCode = $product->getStore()->getWebsite()->getCode();
        $stock = $this->stockResolver->execute(SalesChannelInterface::TYPE_WEBSITE, $websiteCode);

        $isSalable = $this->isProductSalable->execute($product->getSku(), $stock->getStockId());
        $salableObject->setIsSalable($isSalable);
    }
}
