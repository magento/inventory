<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalogApi\Model;

/**
 * Update parent product stock status based on children stock status
 */
interface CompositeProductStockStatusProcessorInterface
{
    /**
     * Update provided products parent products stock status
     *
     * @param array $skus
     * @return void
     */
    public function execute(array $skus): void;
}
