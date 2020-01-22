<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model\SearchRequest;

use Magento\InventoryInStorePickupApi\Api\Data\SearchRequest\AreaInterface;

/**
 * @inheritdoc
 */
class Area implements AreaInterface
{
    /**
     * @var int
     */
    private $radius;

    /**
     * @var string
     */
    private $searchTerm;

    /**
     * @param int $radius
     * @param string $searchTerm
     */
    public function __construct(
        int $radius,
        string $searchTerm
    ) {
        $this->radius = $radius;
        $this->searchTerm = $searchTerm;
    }

    /**
     * @inheritdoc
     */
    public function getRadius(): int
    {
        return $this->radius;
    }

    /**
     * @inheritdoc
     */
    public function getSearchTerm() : string
    {
        return $this->searchTerm;
    }
}
