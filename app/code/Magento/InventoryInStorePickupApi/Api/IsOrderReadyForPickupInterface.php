<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupApi\Api;

interface IsOrderReadyForPickupInterface
{
    /**
     * @param int $orderId
     *
     * @return bool
     */
    public function execute(int $orderId): bool;
}
