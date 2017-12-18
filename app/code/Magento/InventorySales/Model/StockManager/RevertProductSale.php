<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);
namespace Magento\InventorySales\Model\StockManager;

use Magento\Catalog\Model\ProductSkuLocatorInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\InventorySales\Model\StockManager\ReservationsRegister;
use Magento\InventorySalesApi\Api\RevertProductSaleInterface;
use Magento\InventorySales\Model\StockResolverByWebsiteId;

class RevertProductSale implements RevertProductSaleInterface
{
    /**
     * @var StockResolverByWebsiteId
     */
    private $stockResolverByWebsiteId;

    /**
     * @var ProductSkuLocatorInterface
     */
    private $productSkuLocator;

    /**
     * @var ReservationsRegister
     */
    private $reservationsRegister;

    /**
     * RevertProductSale constructor.
     *
     * @param ProductSkuLocatorInterface $productSkuLocator
     * @param StockResolverByWebsiteId   $stockResolverByWebsiteId
     * @param ReservationsRegister       $reservationsRegister
     */
    public function __construct(
        ProductSkuLocatorInterface $productSkuLocator,
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
            $productsQtyBySku[$sku] = (float)$items[$productId];
        }

        $stock = $this->stockResolverByWebsiteId->get((int)$websiteId);

        $this->reservationsRegister->execute($productsQtyBySku, (int)$stock->getStockId());

        return [];
    }
}
