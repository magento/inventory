<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurableProduct\Plugin\CatalogInventory\Api\StockRegistry;

use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\CatalogInventory\Model\Stock;
use Magento\Framework\Exception\LocalizedException;
use Magento\InventoryConfiguration\Model\GetLegacyStockItem;

class SetLegacyStockItemForConfigurable
{
    /**
     * @var GetLegacyStockItem
     */
    private GetLegacyStockItem $getLegacyStockItem;

    /**
     * @param GetLegacyStockItem $getLegacyStockItem
     */
    public function __construct(GetLegacyStockItem $getLegacyStockItem)
    {
        $this->getLegacyStockItem = $getLegacyStockItem;
    }

    /**
     * Set legacy stock for configurable if stock item status changed.
     *
     * @param StockRegistryInterface $subject
     * @param string $productSku
     * @param StockItemInterface $stockItem
     * @return array
     * @throws LocalizedException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeUpdateStockItemBySku(
        StockRegistryInterface $subject,
        string $productSku,
        StockItemInterface $stockItem
    ): array {
        if ($stockItem->getIsInStock() !== Stock::STOCK_OUT_OF_STOCK) {
            $this->getLegacyStockItem->execute($productSku);
        }
        return [$productSku, $stockItem];
    }
}
