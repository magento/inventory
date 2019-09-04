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
    private $cacheTag = 'inv_pl';

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
            $ids[] = sprintf('%s_%s', $this->cacheTag, $item['pickup_location_code']);
        }
        if (!empty($ids)) {
            array_unshift($ids, $this->cacheTag);
        }

        return $ids;
    }
}
