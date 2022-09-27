<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurableProduct\Model;

use Magento\ConfigurableProduct\Model\Inventory\ChangeParentStockStatus;
use Magento\InventoryCatalogApi\Model\CompositeProductStockStatusProcessorInterface;

/**
 * @inheritDoc
 */
class StockStatusProcessor implements CompositeProductStockStatusProcessorInterface
{
    /**
     * @var ChangeParentStockStatus
     */
    private ChangeParentStockStatus $changeParentStockStatus;

    /**
     * @param ChangeParentStockStatus $changeParentStockStatus
     */
    public function __construct(
        ChangeParentStockStatus $changeParentStockStatus
    ) {
        $this->changeParentStockStatus = $changeParentStockStatus;
    }

    /**
     * @inheritDoc
     */
    public function execute(array $productIds): void
    {
        $this->changeParentStockStatus->execute($productIds);
    }
}
