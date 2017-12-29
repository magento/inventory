<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySalesAlert\Model;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\InventorySalesAlert\Api\ProductIsSalableInterface;
use Magento\InventorySales\Model\StockResolver;
use Magento\InventoryApi\Api\IsProductInStockInterface;
use Magento\Framework\App\ObjectManager;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;

class ProductIsSalable implements ProductIsSalableInterface
{

    /**
     * @var \Magento\InventoryApi\Api\IsProductInStockInterface
     */
    private $stockItem;

    /**
     * @var \Magento\InventorySales\Model\StockResolver
     */
    private $stockResolver;

    /**
     * ProductIsSalable constructor.
     *
     * @param \Magento\InventorySales\Model\StockResolver         $stockResolver
     * @param \Magento\InventoryApi\Api\IsProductInStockInterface $stockItem
     */
    public function __construct(
        StockResolver $stockResolver = null,
        IsProductInStockInterface $stockItem = null
    ) {
        $this->stockItem     = $stockItem ?: ObjectManager::getInstance()->get(IsProductInStock::class);
        $this->stockResolver = $stockResolver ?: ObjectManager::getInstance()->get(StockResolver::class);
    }


    /**
     * @param ProductInterface $product
     *
     * @return bool
     */
    public function isSalable(
        ProductInterface $product,
        int $websiteCode = null,
        string $salesChannel = SalesChannelInterface::TYPE_WEBSITE
    ): bool
    {
        $stock = $this->stockResolver->get($salesChannel, $websiteCode);
        if ($stock->getId()) {
            return $this->stockItem->execute($product->getSku(), $stock->getStockId());
        }
        return false;
    }
}