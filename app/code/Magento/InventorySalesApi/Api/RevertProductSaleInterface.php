<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);
namespace Magento\InventorySalesApi\Api;

/**
 * Interface RegisterProductSaleInterface
 * @api
 */
interface RevertProductSaleInterface
{
    /**
     * Revert register product sale
     *
     * @param string[] $items
     * @param int $websiteId
     * @return bool
     */
    public function execute($items, $websiteId = null);
}
