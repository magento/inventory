<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\TestModuleInventoryStateCache\Plugin\InventoryCatalog\Model\IsProductSalableDataStorage;

use Magento\InventoryCatalog\Model\IsProductSalable\IsProductSalableDataStorage;

/**
 * Disable is salable cache storage for integration tests.
 */
class DisableStoragePlugin
{
    /**
     * Disable is salable cache.
     *
     * @param IsProductSalableDataStorage $subject
     * @param \Closure $proceed
     * @param string $sku
     * @param int $stockId
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @return void
     */
    public function aroundGetIsSalable(
        IsProductSalableDataStorage $subject,
        \Closure $proceed,
        string $sku,
        int $stockId
    ): void {
        return;
    }
}
