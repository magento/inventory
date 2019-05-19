<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupApi\Model;

use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryInStorePickupApi\Api\Data\PickupLocationInterface;
use Magento\InventoryInStorePickupApi\Model\Mapper\CreateFromSourceInterface;

/**
 * Create projection of sources on In-Store Pickup context.
 * Data transfer from source to projection will be done according to provided fields mapping.
 *
 * @api
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
     * @var CreateFromSourceInterface
     */
    private $createFromSource;

    /**
     * @param CreateFromSourceInterface $createFromSource
     * @param array $map
     */
    public function __construct(
        CreateFromSourceInterface $createFromSource,
        array $map = []
    ) {
        $this->map = $map;
        $this->createFromSource = $createFromSource;
    }

    /**
     * @param SourceInterface $source
     *
     * @return PickupLocationInterface
     */
    public function map(SourceInterface $source): PickupLocationInterface
    {
        return $this->createFromSource->execute($source, $this->map);
    }
}
