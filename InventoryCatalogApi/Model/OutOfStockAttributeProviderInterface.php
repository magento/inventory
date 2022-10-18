<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalogApi\Model;

interface OutOfStockAttributeProviderInterface
{
    /**
     * Check, if `is_out_of_stock` attribute mapper exists
     *
     * @return bool
     */
    public function isOutOfStockAttributeExists():bool;
}
