<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySalesApi\Model;

use Magento\InventoryApi\Api\Data\StockInterface;

/**
 * Resolve Stock by Website ID
 *
 * @api
 * @since 103.0.2
 */
interface StockByWebsiteIdResolverInterface
{
    /**
     * @param int $websiteId
     * @return StockInterface
     */
    public function execute(int $websiteId): StockInterface;
}
