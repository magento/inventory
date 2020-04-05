<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryBundleProduct\Plugin\Bundle\Model\ResourceModel\Selection\Collection;

use Magento\Bundle\Model\ResourceModel\Selection\Collection;
use Magento\InventorySalesApi\Api\IsProductSalableInterface;
use Magento\InventorySalesApi\Model\StockByWebsiteIdResolverInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Adapt add quantity filter to bundle selection in multi stock environment plugin.
 */
class AdaptAddQuantityFilterPlugin
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
     * @param IsProductSalableInterface $isProductSalable
     * @param StoreManagerInterface $storeManager
     * @param StockByWebsiteIdResolverInterface $stockByWebsiteIdResolver
     */
    public function __construct(
        IsProductSalableInterface $isProductSalable,
        StoreManagerInterface $storeManager,
        StockByWebsiteIdResolverInterface $stockByWebsiteIdResolver
    ) {
        $this->isProductSalable = $isProductSalable;
        $this->storeManager = $storeManager;
        $this->stockByWebsiteIdResolver = $stockByWebsiteIdResolver;
    }

    /**
     * Adapt quantity filter for multi stock environment.
     *
     * @param Collection $subject
     * @param \Closure $proceed
     * @return Collection
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundAddQuantityFilter(
        Collection $subject,
        \Closure $proceed
    ): Collection {
        $website = $this->storeManager->getWebsite();
        $stock = $this->stockByWebsiteIdResolver->execute((int)$website->getId());
        $skusToExclude = [];
        foreach ($subject->getData() as $item) {
            if (!$this->isProductSalable->execute((string)$item['sku'], $stock->getStockId())) {
                $skusToExclude[] = (string)$item['sku'];
            }
        }
        if ($skusToExclude) {
            $subject->getSelect()->where('e.sku NOT IN(?)', implode(',', $skusToExclude));
        }
        $subject->resetData();

        return $subject;
    }
}
