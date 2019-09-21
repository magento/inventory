<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model\Source;

use Magento\InventoryApi\Api\Data\SourceInterface;

/**
 * Anti copy-paste service.
 *
 * Extracts is_pickup_location_active from the source with all the null-checks.
 */
class GetIsPickupLocationActive
{
    /**
     * Extracts is_pickup_location_active from the source with all the null-checks.
     *
     * @param SourceInterface $source
     * @return bool
     */
    public function execute(SourceInterface $source): bool
    {
        $extension = $source->getExtensionAttributes();
        if ($extension) {
            return (bool)$extension->getIsPickupLocationActive();
        }

        return false;
    }
}
