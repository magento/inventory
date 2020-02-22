<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupShipping\Model;

use Magento\InventoryInStorePickupShippingApi\Api\Data\RequestInterface;
use Magento\InventoryInStorePickupShippingApi\Api\GetAvailableLocationsForPickupInterface;

/**
 * @inheritdoc
 */
class GetAvailableLocationsForPickup implements GetAvailableLocationsForPickupInterface
{
    /**
     * @inheritdoc
     */
    public function execute(RequestInterface $request): array
    {
        // TODO: Implement execute() method.
    }
}
