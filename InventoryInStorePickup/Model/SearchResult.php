<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model;

use InvalidArgumentException;
use Magento\InventoryInStorePickupApi\Api\Data\PickupLocationInterface;
use Magento\InventoryInStorePickupApi\Api\Data\SearchCriteria\GetNearbyLocationsCriteriaInterface;
use Magento\InventoryInStorePickupApi\Api\Data\SearchResultInterface;

/**
 * @inheritdoc
 */
class SearchResult implements SearchResultInterface
{
    /**
     * @var PickupLocationInterface[]
     */
    private $items = [];

    /**
     * @var int
     */
    private $totalCount = 0;

    /**
     * @var GetNearbyLocationsCriteriaInterface
     */
    private $searchCriteria;

    /**
     * @param PickupLocationInterface[] $items
     * @param int $totalCount
     * @param GetNearbyLocationsCriteriaInterface|null $searchCriteria
     */
    public function __construct(
        array $items,
        int $totalCount = 0,
        $searchCriteria = null
    ) {
        $this->items = $items;
        $this->totalCount = $totalCount;
        $this->searchCriteria = $searchCriteria;
    }

    /**
     * @inheritDoc
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * @inheritDoc
     */
    public function setItems(array $items): SearchResultInterface
    {
        $this->validateItems($items);
        $this->items = $items;

        return $this->items;
    }

    /**
     * @inheritDoc
     */
    public function getSearchCriteria(): GetNearbyLocationsCriteriaInterface
    {
        return $this->searchCriteria;
    }

    /**
     * @inheritDoc
     */
    public function setSearchCriteria(GetNearbyLocationsCriteriaInterface $searchCriteria): SearchResultInterface
    {
        $this->searchCriteria = $searchCriteria;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getTotalCount(): int
    {
        return $this->totalCount;
    }

    /**
     * @inheritDoc
     */
    public function setTotalCount(int $totalCount): SearchResultInterface
    {
        $this->totalCount = $totalCount;

        return $this;
    }

    /**
     * Validate Pickup Location objects.
     *
     * @param array $items
     * @return void
     * @throws InvalidArgumentException
     */
    private function validateItems(array $items): void
    {
        foreach ($items as $item) {
            if (!$item instanceof PickupLocationInterface) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Items should be an instance of %s, $s given',
                        [
                            PickupLocationInterface::class,
                            is_object($item) ? get_class($item) : gettype($item)
                        ]
                    )
                );
            }
        }
    }
}
