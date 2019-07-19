<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupApi\Api;

use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryInStorePickupApi\Api\Data\PickupLocationInterface;

/**
 * Create projection of sources on In-Store Pickup context.
 *
 * @api
 */
interface MapperInterface
{
    /**
     * Create projection of sources on In-Store Pickup context.
     *
     * @param SourceInterface $source
     * @return PickupLocationInterface
     */
    public function map(SourceInterface $source): PickupLocationInterface;
}
