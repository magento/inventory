<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Model;

use Magento\InventorySalesApi\Model\GetStockItemDataInterface;
use Magento\InventoryConfigurationApi\Api\GetStockItemConfigurationInterface;
use Magento\InventoryConfigurationApi\Api\Data\StockItemConfigurationInterface;
use Magento\InventorySalesApi\Api\GetProductSalableQtyInterface;

/**
 * GetBackorderQty class
 *
 * Returns the amount of items to be backordered, based on the product sku, stockId
 * and requested quantity
 */
class GetBackorderQty
{
    /**
     * @var GetStockItemConfigurationInterface
     */
    private $getStockItemConfiguration;

    /**
     * @var GetStockItemDataInterface
     */
    private $getStockItemData;

    /**
     * @var GetProductSalableQtyInterface
     */
    private $getProductSalableQty;

    /**
     * @param GetStockItemConfigurationInterface $getStockItemConfig
     * @param GetStockItemDataInterface $getStockItemData
     * @param GetProductSalableQtyInterface $getProductSalableQty
     */
    public function __construct(
        GetStockItemConfigurationInterface $getStockItemConfig,
        GetStockItemDataInterface $getStockItemData,
        GetProductSalableQtyInterface $getProductSalableQty
    ) {
        $this->getStockItemConfiguration = $getStockItemConfig;
        $this->getStockItemData = $getStockItemData;
        $this->getProductSalableQty = $getProductSalableQty;
    }

    /**
     * Main execute function
     *
     * Calculate the amount of a particular product that is to be backordered based on the
     * salable quantity available, given its sku, stockId and the amount requested.
     *
     * @param string $sku
     * @param integer $stockId
     * @param float $requestedQty
     * @return float
     */
    public function execute(string $sku, int $stockId, float $requestedQty): float
    {
        $stockItemConfiguration = $this->getStockItemConfiguration->execute($sku, $stockId);
        $backOrderQty = 0;

        if ($stockItemConfiguration->isManageStock()
            && ($stockItemConfiguration->getBackorders() === StockItemConfigurationInterface::BACKORDERS_YES_NOTIFY ||
                $stockItemConfiguration->getBackorders() === StockItemConfigurationInterface::BACKORDERS_YES_NONOTIFY)
        ) {
            $stockItemData = $this->getStockItemData->execute($sku, $stockId);
            if (null === $stockItemData) {
                return $backOrderQty;
            }

            $salableQty = $this->getProductSalableQty->execute($sku, $stockId);
            /**
             * Salable Quantity has had minQty subtracted from it. This means that when minqty is a negative value
             * it is increasing the value of salable quantity to incorporate the fact that we are allowed to sell
             * more items than in stock. When calculating backorder quantity to be displayed to customer and to be
             * logged in sales_order_item, we don't want salable qty to include this, we want the backorder qty to
             * indicate how many more items than we have in stock are being ordered including any existing
             * reservations.
             *
             * Example calculation
             *
             * instock = 10
             * minqty = -10
             *
             * Therefore $salableQty = 20
             *
             * $requestedQty = 15
             *
             * Therefore the amount to be backordered would be 15 - 20 - (-10) = 5
             */
            $minqty = $stockItemConfiguration->getMinQty();
            $backOrderQty = $requestedQty - $salableQty - $minqty;
            /**
             * In cases of more stock available than being ordered, the above calculation returns a
             * negative result indicating more stock available than requested. Set to zero in this case
             */
            if ($backOrderQty < 0) {
                $backOrderQty = 0;
            }
            /**
             * Catch the situation where we are already in backorders. e.g 0 in stock, reservations already
             * in for 5 items on other orders, and we get a request for 5 more. Without this check, we would
             * end up notifying the customer that 10 needed to be backorderd (10 backorderd items are required
             * to satisfy this order plus all reservations), but only 5 of them are relevant to this
             * request/customer/order so always cap the backOrderQty to the amount requested.
             */
            if (bccomp((string)$requestedQty, (string)$backOrderQty, 4) < 0) {
                $backOrderQty = $requestedQty;
            }
        }
        return $backOrderQty;
    }
}
