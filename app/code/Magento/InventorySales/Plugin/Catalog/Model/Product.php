<?php
declare(strict_types=1);

namespace Magento\InventorySales\Plugin\Catalog\Model;

use Magento\Catalog\Model\Product as Subject;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\IsProductSalableInterface;
use Magento\InventorySalesApi\Api\StockResolverInterface;

/**
 * Check if product is salable in MSI
 */
class Product
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
     * @param Subject $subject
     * @param bool $result
     * @return bool
     */
    public function afterIsSalable(Subject $subject, bool $result): bool
    {
        if (!$result) {
            return $result;
        }

        $websiteCode = $subject->getStore()->getWebsite()->getCode();
        $stock = $this->stockResolver->execute(SalesChannelInterface::TYPE_WEBSITE, $websiteCode);

        return $this->isProductSalable->execute($subject->getSku(), $stock->getStockId());
    }
}
