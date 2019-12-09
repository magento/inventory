<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model\SearchResult\Strategy;

use Magento\Framework\Api\SortOrder;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryApi\Api\Data\SourceSearchResultsInterface;
use Magento\InventoryInStorePickup\Model\SearchRequest\Area\GetDistanceToSources;
use Magento\InventoryInStorePickupApi\Api\Data\SearchRequest\AreaInterface;
use Magento\InventoryInStorePickupApi\Api\Data\SearchRequestInterface;
use Magento\InventoryInStorePickupApi\Model\SearchResult\StrategyInterface;

/**
 * Work with Distance Based data set.
 *
 * This assume that we need to sort data by distance if Sort by distance is requested or no other sorts are provided.
 */
class DistanceBased implements StrategyInterface
{
    /**
     * @var GetDistanceToSources
     */
    private $getDistanceToSources;

    /**
     * @param GetDistanceToSources $getDistanceToSources
     */
    public function __construct(GetDistanceToSources $getDistanceToSources)
    {
        $this->getDistanceToSources = $getDistanceToSources;
    }

    /**
     * @inheritdoc
     *
     * @throws NoSuchEntityException
     */
    public function getSources(
        SearchRequestInterface $searchRequest,
        SourceSearchResultsInterface $sourcesSearchResult
    ): array {
        $sortOrder = $this->getDistanceSort($searchRequest);
        $distanceToSources = $this->getDistanceToSources->execute($searchRequest->getArea());
        $sources = $sourcesSearchResult->getItems();

        if ($sortOrder) {
            $sources = $this->sortSourcesByDistance($sources, $distanceToSources, $sortOrder->getDirection());
        }

        if ($searchRequest->getSort() === null) {
            $sources = $this->sortSourcesByDistance($sources, $distanceToSources);
        }

        return $sources;
    }

    /**
     * Sort Sources by Distance.
     *
     * @param array $sources
     * @param array $distanceToSources
     * @param string $sortDirection
     *
     * @return array
     */
    private function sortSourcesByDistance(
        array $sources,
        array $distanceToSources,
        string $sortDirection = SortOrder::SORT_ASC
    ): array {
        $ascSort = function (SourceInterface $left, SourceInterface $right) use ($distanceToSources) {
            return $distanceToSources[$left->getSourceCode()] <=> $distanceToSources[$right->getSourceCode()];
        };

        $descSort = function (SourceInterface $left, SourceInterface $right) use ($distanceToSources) {
            return $distanceToSources[$right->getSourceCode()] <=> $distanceToSources[$left->getSourceCode()];
        };

        $sort = $sortDirection === SortOrder::SORT_ASC ? $ascSort : $descSort;

        usort($sources, $sort);

        return $sources;
    }

    /**
     * Get distance Sort from list of Sorts.
     *
     * @param SearchRequestInterface $searchRequest
     *
     * @return SortOrder|null
     */
    private function getDistanceSort(SearchRequestInterface $searchRequest): ?SortOrder
    {
        $sorts = $searchRequest->getSort();

        if ($sorts === null) {
            return null;
        }

        foreach ($sorts as $sortOrder) {
            if ($sortOrder->getField() === AreaInterface::DISTANCE_FIELD) {
                return $sortOrder;
            }
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public function isApplicable(
        SearchRequestInterface $searchRequest,
        SourceSearchResultsInterface $sourcesSearchResult
    ): bool {
        return (bool)$searchRequest->getArea();
    }
}
