<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Plugin\StockState;

use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\Quote\Model\Quote\Item;
use Magento\CatalogInventory\Model\Quote\Item\QuantityValidator\Initializer\StockItem;

/**
 * StockItemPlugin Class
 *
 * Plugin for Magento\CatalogInventory\Model\Quote\Item\QuantityValidator\Initializer\StockItem
 *
 * Takes the website_id from the quoteitems's product and adds it to the StockItems as an Extension
 * Attribute. This ensures that in downstream processing, particularly in the
 * Magento\InventorySales\Plugin\StockState\ProviderCheckQuoteItemQtyPlugin Class, that the current
 * website id can be accessed.
 */
class StockItemPlugin
{
    /**
     * StockItemPlugin::beforeInitialize
     *
     * Add the current website id to $stockItem as an extension attribute
     *
     * @param StockItem $subject
     * @param StockItemInterface $stockItem
     * @param Item $quoteItem
     * @param float $qty
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeInitialize(
        StockItem $subject,
        StockItemInterface $stockItem,
        Item $quoteItem,
        $qty
    ) {
        $ext = $stockItem->getExtensionAttributes();
        $ext->setWebsiteId($quoteItem->getProduct()->getStore()->getWebsiteId());
        $stockItem->setExtensionAttributes($ext);
        return [$stockItem,$quoteItem,$qty];
    }
}
