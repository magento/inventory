<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);
namespace Magento\InventorySales\Model\StockManager;

use Magento\Framework\Exception\LocalizedException;
use Magento\InventorySales\Model\StockManager\ReservationsRegister;
use Magento\InventorySalesApi\Api\RegisterProductSaleInterface;
use Magento\InventorySales\Model\StockResolverByWebsiteId;

class RegisterProductSale implements RegisterProductSaleInterface
{
    /**
     * @var StockResolverByWebsiteId
     */
    private $stockResolverByWebsiteId;

    /**
     * @var \Magento\Catalog\Model\ProductSkuLocatorInterface
     */
    private $productSkuLocator;

    /**
     * @var \Magento\InventorySales\Model\StockManager\ReservationsRegister
     */
    private $reservationsRegister;

    /**
     * RegisterProductSale constructor.
     *
     * @param \Magento\Catalog\Model\ProductSkuLocatorInterface               $productSkuLocator
     * @param StockResolverByWebsiteId                                        $stockResolverByWebsiteId
     * @param \Magento\InventorySales\Model\StockManager\ReservationsRegister $reservationsRegister
     */
    public function __construct(
        \Magento\Catalog\Model\ProductSkuLocatorInterface $productSkuLocator,
        StockResolverByWebsiteId $stockResolverByWebsiteId,
        ReservationsRegister $reservationsRegister
    ) {
        $this->stockResolverByWebsiteId = $stockResolverByWebsiteId;
        $this->productSkuLocator = $productSkuLocator;
        $this->reservationsRegister = $reservationsRegister;
    }

    /**
     * @inheritdoc
     */
    public function execute($items, $websiteId = null)
    {
        if (!$websiteId) {
            //TODO: is we need to throw exception?
            throw new LocalizedException(__('$websiteId is required'));
        }
        $productSkus = $this->productSkuLocator->retrieveSkusByProductIds(array_keys($items));
        $productsQtyBySku = [];
        foreach ($productSkus as $productId => $sku) {
            $productsQtyBySku[$sku] = -(float)$items[$productId];
        }

        $stock = $this->stockResolverByWebsiteId->get((int)$websiteId);

        $this->reservationsRegister->execute($productsQtyBySku, $stock->getStockId());

        return [];
    }
}
