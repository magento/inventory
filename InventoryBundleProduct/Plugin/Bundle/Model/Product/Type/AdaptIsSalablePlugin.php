<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryBundleProduct\Plugin\Bundle\Model\Product\Type;

use Magento\Bundle\Model\Product\Type;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\InventoryBundleProduct\Model\GetBundleProductStockStatus;
use Magento\InventorySalesApi\Api\IsProductSalableInterface;
use Magento\InventorySalesApi\Model\StockByWebsiteIdResolverInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Adapt 'Is Salable' for bundle product for multi stock environment plugin.
 */
class AdaptIsSalablePlugin
{
    /**
     * @var IsProductSalableInterface
     */
    private $isProductSalable;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var StockByWebsiteIdResolverInterface
     */
    private $stockByWebsiteIdResolver;

    /**
     * @var GetBundleProductStockStatus
     */
    private $getBundleProductStockStatus;

    /**
     * @param IsProductSalableInterface $isProductSalable
     * @param StoreManagerInterface $storeManager
     * @param StockByWebsiteIdResolverInterface $stockByWebsiteIdResolver
     * @param GetBundleProductStockStatus $getBundleProductStockStatus
     */
    public function __construct(
        IsProductSalableInterface $isProductSalable,
        StoreManagerInterface $storeManager,
        StockByWebsiteIdResolverInterface $stockByWebsiteIdResolver,
        GetBundleProductStockStatus $getBundleProductStockStatus
    ) {
        $this->isProductSalable = $isProductSalable;
        $this->storeManager = $storeManager;
        $this->stockByWebsiteIdResolver = $stockByWebsiteIdResolver;
        $this->getBundleProductStockStatus = $getBundleProductStockStatus;
    }

    /**
     * Verify, is product salable in multi stock environment.
     *
     * @param Type $subject
     * @param \Closure $proceed
     * @param Product $product
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundIsSalable(Type $subject, \Closure $proceed, Product $product): bool
    {
        $salable = $product->getStatus() == Status::STATUS_ENABLED;
        if ($salable && $product->hasData('is_salable')) {
            $salable = $product->getData('is_salable');
        }

        if (!(bool)(int)$salable) {
            return false;
        }

        if ($product->hasData('all_items_salable')) {
            return $product->getData('all_items_salable');
        }

        $website = $this->storeManager->getWebsite();
        $stock = $this->stockByWebsiteIdResolver->execute((int)$website->getId());
        $options = $subject->getOptionsCollection($product);
        $isSalable = $this->getBundleProductStockStatus->execute($product, $options->getItems(), $stock->getStockId());
        $product->setData('all_items_salable', $isSalable);

        return $isSalable;
    }
}
