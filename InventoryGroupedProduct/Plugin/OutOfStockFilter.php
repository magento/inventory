<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryGroupedProduct\Plugin;

use Magento\Framework\DataObject;
use Magento\Catalog\Model\Product;
use Magento\GroupedProduct\Model\Product\Type\Grouped;
use Magento\Store\Model\StoreManagerInterface;
use Magento\InventorySalesApi\Api\IsProductSalableInterface;
use Magento\InventorySalesApi\Api\StockResolverInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;

/**
 * Removes out of stock products from cart candidates when appropriate.
 */
class OutOfStockFilter
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var IsProductSalableInterface
     */
    private $isProductSalable;

    /**
     * @var StockResolverInterface
     */
    private $stockResolver;

    /**
     * @param StoreManagerInterface $storeManager
     * @param IsProductSalableInterface $isProductSalable
     * @param StockResolverInterface $stockResolver
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        IsProductSalableInterface $isProductSalable,
        StockResolverInterface $stockResolver
    ) {
        $this->storeManager = $storeManager;
        $this->isProductSalable = $isProductSalable;
        $this->stockResolver = $stockResolver;
    }

    /**
     * Removes out of stock products for requests that don't specify the super group.
     *
     * @param Grouped $subject
     * @param array|string $result
     * @param \Magento\Framework\DataObject $buyRequest
     * @return string|array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterPrepareForCartAdvanced(Grouped $subject, $result, DataObject $buyRequest)
    {
        if (!is_array($result) && $result instanceof Product) {
            $result = [$result];
        }

        // Only remove out-of-stock products if no quantities were specified
        if (is_array($result) && !empty($result) && !$buyRequest->getData('super_group')) {
            $websiteCode = $this->storeManager->getWebsite()
                ->getCode();
            $stockId = $this->stockResolver->execute(SalesChannelInterface::TYPE_WEBSITE, $websiteCode)
                ->getStockId();

            foreach ($result as $index => $cartItem) {
                if (!$this->isProductSalable->execute($cartItem->getSku(), $stockId)) {
                    unset($result[$index]);
                }
            }
        }

        return $result;
    }
}
