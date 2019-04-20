<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model\PickupLocation;

use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryInStorePickup\Model\PickupLocation\Mapper\CreateFromSource;
use Magento\InventoryInStorePickupApi\Api\Data\PickupLocationInterface;

/**
 * Create projection of sources on In-Store Pickup context.
 * Data transfer from source to projection will be done according to provided fields mapping.
 */
class Mapper
{
    /**
     * Attributes map for projection.
     *
     * @var array
     */
    private $map;

    /**
     * @var \Magento\InventoryInStorePickup\Model\PickupLocation\Mapper\CreateFromSource
     */
    private $createFromSource;

    /**
     * @param \Magento\InventoryInStorePickup\Model\PickupLocation\Mapper\CreateFromSource $createFromSource
     * @param array $map
     */
    public function __construct(
        CreateFromSource $createFromSource,
        array $map = []
    ) {
        $this->map = $map;
        $this->createFromSource = $createFromSource;
    }

    /**
     * @param \Magento\InventoryApi\Api\Data\SourceInterface $source
     *
     * @return \Magento\InventoryInStorePickupApi\Api\Data\PickupLocationInterface
     */
    public function map(SourceInterface $source): PickupLocationInterface
    {
        return $this->createFromSource->execute($source, $this->map);
    }
}
