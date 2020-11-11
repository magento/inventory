<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Plugin\CatalogInventory\Model\StockRegistryStorage;

use Magento\CatalogInventory\Model\StockRegistryStorage;
use Magento\InventoryCatalog\Model\IsProductSalable\IsProductSalableDataStorage;

/**
 * Clean IsProductSalableDataStorage in case StockRegistryStorage has been cleaned up.
 */
class CleanIsSalableDataStoragePlugin
{
    /**
     * @var IsProductSalableDataStorage
     */
    private $isProductSalableDataStorage;

    /**
     * @param IsProductSalableDataStorage $isProductSalableDataStorage
     */
    public function __construct(IsProductSalableDataStorage $isProductSalableDataStorage)
    {
        $this->isProductSalableDataStorage = $isProductSalableDataStorage;
    }

    /**
     * Clean is product salable storage after stock registry storage has been cleaned.
     *
     * @param StockRegistryStorage $subject
     * @param void $result
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterClean(StockRegistryStorage $subject, $result): void
    {
        $this->isProductSalableDataStorage->cleanIsSalable();
    }
}
