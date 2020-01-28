<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Model;

use Magento\InventoryConfigurationApi\Api\Data\StockItemConfigurationInterface;
use Magento\InventoryConfigurationApi\Api\GetStockItemConfigurationInterface;
use Magento\InventorySalesApi\Api\GetProductSalableQtyInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\InventoryConfigurationApi\Exception\SkuIsNotAssignedToStockException;

/**
 * GetBackOrderQty Class
 *
 * Determine the number of back ordered items for new order, given current stock levels and reservations.
 */
class GetBackOrderQty
{
    /**
     * @var GetStockItemConfigurationInterface
     */
    private $getStockItemConfiguration;

    /**
     * @var GetProductSalableQtyInterface
     */
    private $getProductSalableQty;

    /**
     * @param GetStockItemConfigurationInterface $getStockItemConfiguration
     * @param GetProductSalableQtyInterface $getProductSalableQty
     */
    public function __construct(
        GetStockItemConfigurationInterface $getStockItemConfiguration,
        GetProductSalableQtyInterface $getProductSalableQty
    ) {
        $this->getStockItemConfiguration = $getStockItemConfiguration;
        $this->getProductSalableQty = $getProductSalableQty;
    }

    /**
     * GetBackOrderQty::execute
     *
     * Given a product SKU and a Stock ID, this determines how many items would need
     * to be back ordered, given the requested quantity. This is calculated taking
     * into account the salable quantity and minQty setting. (Note: Salable Quantity
     * takes into account current reservations)
     *
     * Thus, as an example, if we have 10 physical stock, minQty is set to -20 (i.e allow
     * stock to go to -20 before denying sales), salable quantity would be 30, but a request
     * for 15 items should result in a backorder quantity of 5.
     *
     * @param string $sku
     * @param int $stockId
     * @param float $requestedQty
     * @return float
     * @throws InputException
     * @throws LocalizedException
     * @throws SkuIsNotAssignedToStockException
     */
    public function execute(string $sku, int $stockId, float $requestedQty): float
    {
        $backOrderQty = 0;
        $stockItemConfiguration = $this->getStockItemConfiguration->execute($sku, $stockId);

        if ($stockItemConfiguration->getBackorders() === StockItemConfigurationInterface::BACKORDERS_YES_NOTIFY
        || $stockItemConfiguration->getBackorders() === StockItemConfigurationInterface::BACKORDERS_YES_NONOTIFY) {
            $salableQty = $this->getProductSalableQty->execute($sku, $stockId);
            $minqty = $stockItemConfiguration->getMinQty();
            $backOrderQty = $requestedQty - $salableQty - $minqty;

            /**
             * in cases of plenty of stock available, $backOrderQty calculation returns a negative result indicating
             * more stock available than requested. Set to zero in this case
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
