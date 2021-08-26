<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\InventoryCatalog\Plugin\CatalogInventory\Api\StockRegistry;

use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\InventoryCatalog\Model\ResourceModel\SetDataToLegacyStockStatus;

class AdaptUpdateStockStatusBySkuPlugin
{
    /**
     * @var SetDataToLegacyStockStatus
     */
    private $setDataToLegacyStockStatus;

    /**
     * @param SetDataToLegacyStockStatus $setDataToLegacyStockStatus
     */
    public function __construct(
        SetDataToLegacyStockStatus $setDataToLegacyStockStatus
    ) {
        $this->setDataToLegacyStockStatus = $setDataToLegacyStockStatus;
    }

    /**
     * @param StockRegistryInterface $subject
     * @param int $itemId
     * @param string $productSku
     * @param StockItemInterface $stockItem
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterUpdateStockItemBySku(
        StockRegistryInterface $subject,
        int $itemId,
        string $productSku,
        StockItemInterface $stockItem
    ): void {
        $this->setDataToLegacyStockStatus->execute(
            $productSku,
            (float)$stockItem->getQty(),
            $stockItem->getIsInStock()
        );
    }
}
