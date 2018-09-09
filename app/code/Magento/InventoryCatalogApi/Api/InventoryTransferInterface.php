<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalogApi\Api;

/**
 * Perform bulk product inventory transfer
 *
 * @api
 */
interface InventoryTransferInterface
{
    /**
     * Run bulk inventory transfer
     * @param string $sku
     * @param string $originSource
     * @param string $destinationSource
     * @param bool $unassignFromOrigin
     * @return bool
     */
    public function execute(
        string $sku,
        string $originSource,
        string $destinationSource,
        bool $unassignFromOrigin
    ): bool;
}
