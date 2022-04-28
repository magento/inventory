<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Plugin\Catalog\Model;

use Magento\Catalog\Model\Product as Subject;
use Magento\InventoryApi\Api\Data\StockInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\AreProductsSalableInterface;
use Magento\InventorySalesApi\Api\StockResolverInterface;

/**
 * Check if product is salable in MSI
 */
class IsSalable
{
    /**
     * @var AreProductsSalableInterface
     */
    private $areProductsSalable;

    /**
     * @var StockResolverInterface
     */
    private $stockResolver;

    /**
     * @var array
     */
    private $stock;

    /**
     * @param AreProductsSalableInterface $areProductsSalable
     * @param StockResolverInterface $stockResolver
     */
    public function __construct(
        AreProductsSalableInterface $areProductsSalable,
        StockResolverInterface $stockResolver
    ) {
        $this->areProductsSalable = $areProductsSalable;
        $this->stockResolver = $stockResolver;
    }

    /**
     * @param Subject $subject
     * @param \Closure $proceed
     * @param bool $result
     * @return bool
     */
    public function aroundIsSalable(Subject $subject, \Closure $proceed): bool
    {
        $websiteCode = $subject->getStore()->getWebsite()->getCode();
        return $this->getProductStock($subject->getSku(), $websiteCode);
    }

    /**
     * @param string $sku
     * @param string $websiteCode
     * @return bool
     */
    private function getProductStock(string $sku, string $websiteCode): bool
    {
        if (empty($this->stock[$sku])) {
            $stock = $this->stockResolver->execute(SalesChannelInterface::TYPE_WEBSITE, $websiteCode);
            $results = $this->areProductsSalable->execute([$sku], $stock->getStockId());
            $result = \reset($results);
            $this->stock[$sku] = $result['isSalable'];
        }

        return $this->stock[$sku];
    }
}
