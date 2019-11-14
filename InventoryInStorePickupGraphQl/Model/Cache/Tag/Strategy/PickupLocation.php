<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupGraphQl\Model\Cache\Tag\Strategy;

use Magento\Framework\App\Cache\Tag\StrategyInterface;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryInStorePickupGraphQl\Model\Resolver\PickupLocations\Identity;

/**
 * Produce cache tags for Pickup Location.
 */
class PickupLocation implements StrategyInterface
{
    /**
     * @inheritdoc
     */
    public function getTags($object): array
    {
        if (!is_object($object)) {
            throw new \InvalidArgumentException('Provided argument is not an object');
        }

        if ($object instanceof SourceInterface) {
            return [Identity::CACHE_TAG];
        }

        return [];
    }
}
