<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurableProduct\Plugin\CatalogInventory;

use Magento\Catalog\Model\ResourceModel\GetProductTypeById;
use Magento\CatalogInventory\Model\ResourceModel\Stock\Item as ItemResourceModel;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\Model\AbstractModel as StockItem;
use Magento\InventoryCatalogApi\Model\IsSingleSourceModeInterface;
use Magento\InventoryConfigurableProduct\Model\StockStatusManagement;

class UpdateLegacyStockStatusForConfigurableProduct
{
    /**
     * @param GetProductTypeById $getProductTypeById
     * @param IsSingleSourceModeInterface $isSingleSourceMode
     * @param StockStatusManagement $stockStatusManagement
     */
    public function __construct(
        private readonly GetProductTypeById $getProductTypeById,
        private readonly IsSingleSourceModeInterface $isSingleSourceMode,
        private readonly StockStatusManagement $stockStatusManagement
    ) {
    }

    /**
     * Updates stock item for new configurable product based on children stock items.
     *
     * @param ItemResourceModel $subject
     * @param StockItem $stockItem
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeSave(
        ItemResourceModel $subject,
        StockItem $stockItem
    ) {
        if ($this->isSingleSourceMode->execute()
            && $this->getProductTypeById->execute($stockItem->getProductId()) === Configurable::TYPE_CODE
            && !$stockItem->hasStockStatusChangedAutomaticallyFlag()
        ) {
            $this->stockStatusManagement->update($stockItem);
        }
    }
}
