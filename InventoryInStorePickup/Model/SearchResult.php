<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model;

use InvalidArgumentException;
use Magento\InventoryInStorePickupApi\Api\Data\PickupLocationInterface;
use Magento\InventoryInStorePickupApi\Api\Data\SearchRequestInterface;
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
     * @var SearchRequestInterface
     */
    private $searchRequest;

    /**
     * @param PickupLocationInterface[] $items
     * @param int $totalCount
     * @param SearchRequestInterface|null $searchRequest
     */
    public function __construct(
        array $items,
        int $totalCount = 0,
        $searchRequest = null
    ) {
        $this->items = $items;
        $this->totalCount = $totalCount;
        $this->searchRequest = $searchRequest;
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
                        'Expected instance of %s, %s given instead.',
                        PickupLocationInterface::class,
                        is_object($item) ? get_class($item) : 'not object type'
                    )
                );
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function getSearchRequest(): SearchRequestInterface
    {
        return $this->searchRequest;
    }

    /**
     * @inheritdoc
     */
    public function setSearchRequest(SearchRequestInterface $searchRequest): SearchResultInterface
    {
        $this->searchRequest = $searchRequest;
        return $this;
    }
}
