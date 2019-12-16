<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model\SearchRequest\Builder;

use Magento\InventoryInStorePickupApi\Api\Data\SearchRequest\AreaInterface;
use Magento\InventoryInStorePickupApi\Api\Data\SearchRequest\AreaInterfaceFactory;

/**
 * Distance Filter Builder.
 */
class AreaBuilder
{
    private const RADIUS = 'radius';
    private const SEARCH_TERM = 'searchTerm';

    /**
     * Filter data.
     *
     * @var array
     */
    private $data = [];

    /**
     * @var AreaInterfaceFactory
     */
    private $areaFactory;

    /**
     * @param AreaInterfaceFactory $areaFactory
     */
    public function __construct(AreaInterfaceFactory $areaFactory)
    {
        $this->areaFactory = $areaFactory;
    }

    /**
     * Build Distance Filter.
     *
     * @return AreaInterface|null
     */
    public function create(): ?AreaInterface
    {
        $data = $this->data;
        $this->data = [];

        return empty($data) ? null : $this->areaFactory->create($data);
    }

    /**
     * Set area Radius.
     *
     * @param int $radius
     *
     * @return self
     */
    public function setRadius(int $radius): self
    {
        $this->data[self::RADIUS] = $radius;
        return $this;
    }

    /**
     * Set area search term.
     *
     * @param string $searchTerm
     *
     * @return self
     */
    public function setSearchTerm(string $searchTerm): self
    {
        $this->data[self::SEARCH_TERM] = $searchTerm;
        return $this;
    }
}
