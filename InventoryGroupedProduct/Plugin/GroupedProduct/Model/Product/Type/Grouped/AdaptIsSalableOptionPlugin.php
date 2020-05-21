<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryGroupedProduct\Plugin\GroupedProduct\Model\Product\Type\Grouped;

use Magento\Catalog\Model\ResourceModel\Product\Link\Product\Collection;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\GroupedProduct\Model\Product\Type\Grouped;
use Magento\InventorySalesApi\Api\AreProductsSalableInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\StockResolverInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Filter only salable grouped options plugin.
 */
class AdaptIsSalableOptionPlugin
{
    /**
     * @var AreProductsSalableInterface
     */
    private $areProductsSalable;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var StockResolverInterface
     */
    private $stockResolver;

    /**
     * @param AreProductsSalableInterface $areProductsSalable
     * @param StockResolverInterface $stockResolver
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        AreProductsSalableInterface $areProductsSalable,
        StockResolverInterface $stockResolver,
        StoreManagerInterface $storeManager
    ) {
        $this->areProductsSalable = $areProductsSalable;
        $this->storeManager = $storeManager;
        $this->stockResolver = $stockResolver;
    }

    /**
     * Filter salable grouped options.
     *
     * @param Grouped $subject
     * @param Collection $result
     * @return Collection
     * @throws NoSuchEntityException in case there is no stock connected to given website.
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetAssociatedProductCollection(Grouped $subject, Collection $result): Collection
    {
        $website = $this->storeManager->getStore($result->getStoreId())->getWebsite();
        $stock = $this->stockResolver->execute(SalesChannelInterface::TYPE_WEBSITE, $website->getCode());
        $skus = array_column($result->getData(), 'sku');
        $areProductsSalable = $this->areProductsSalable->execute($skus, $stock->getStockId());
        $sksToInclude = [];
        foreach ($areProductsSalable as $salableResult) {
            if ($salableResult->isSalable()) {
                $sksToInclude[] = $salableResult->getSku();
            }
        }
        $result->addAttributeToFilter('sku', ['in' => $sksToInclude]);

        return $result;
    }
}
