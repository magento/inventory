<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupGraphQl\Model\Resolver\PickupLocations;

use Magento\Framework\GraphQl\Query\Resolver\IdentityInterface;

/**
 * Identity for resolved Pickup Locations.
 */
class Identity implements IdentityInterface
{
    /**
     * @var string
     */
    const CACHE_TAG = 'inv_pl';

    /**
     * Get identity for Pickup Locations.
     *
     * @param array $resolvedData
     *
     * @return string[]
     */
    public function getIdentities(array $resolvedData): array
    {
        $ids = [];
        $items = $resolvedData['items'] ?? [];
        foreach ($items as $item) {
            $ids[] = sprintf('%s_%s', self::CACHE_TAG, $item['pickup_location_code']);
        }
        if (!empty($ids)) {
            array_unshift($ids, self::CACHE_TAG);
        }

        return $ids;
    }
}
