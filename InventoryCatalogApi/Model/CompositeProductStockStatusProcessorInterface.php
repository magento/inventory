<?php
/**
 * Copyright 2022 Adobe
 * All Rights Reserved.
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
