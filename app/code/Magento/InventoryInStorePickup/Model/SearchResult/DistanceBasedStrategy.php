<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model\SearchResult;

use Magento\Framework\Api\Filter;
use Magento\Framework\Api\SortOrder;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryApi\Api\Data\SourceSearchResultsInterface;
use Magento\InventoryInStorePickupApi\Api\Data\SearchRequest\DistanceFilterInterface;
use Magento\InventoryInStorePickupApi\Api\Data\SearchRequest\FilterInterface;
use Magento\InventoryInStorePickupApi\Api\Data\SearchRequestInterface;
use Magento\InventoryInStorePickupApi\Model\SearchResult\ExtractStrategyInterface;
use Magento\Tests\NamingConvention\true\string;

/**
 * Work with Distance Based data set.
 * This assume next statements:
 * 1. Data already filtered by Scope
 * 2. Sort data by distance if Sort by distance is requested or no other sorts are provided.
 */
class DistanceBasedStrategy implements ExtractStrategyInterface
{
    /**
     * @inheritdoc
     */
    public function getSources(
        SearchRequestInterface $searchRequest,
        SourceSearchResultsInterface $sourcesSearchResult
    ): array {
        $sortOrder = $this->getDistanceSort($searchRequest);
        $sourceCodes = $this->getSortedCodes($searchRequest, $sourcesSearchResult);
        $sources = $sourcesSearchResult->getItems();

        if ($sortOrder) {
            $sources = $this->sortSourcesByDistance($sources, $sourceCodes, $sortOrder->getDirection());
        }

        if ($searchRequest->getSort() === null) {
            $sources = $this->sortSourcesByDistance($sources, $sourceCodes);
        }

        return $sources;
    }

    /**
     * Get Source Codes used for Distance Based filter from Search Result.
     *
     * @param SearchRequestInterface $searchRequest
     * @param SourceSearchResultsInterface $searchResults
     *
     * @return array
     */
    private function getSortedCodes(SearchRequestInterface $searchRequest, SourceSearchResultsInterface $searchResults): array
    {
        $searchCriteria = $searchResults->getSearchCriteria();
        foreach ($searchCriteria->getFilterGroups() as $filterGroup) {
            foreach ($filterGroup->getFilters() as $filter) {
                if ($this->isDistanceCodesFilter($filter, $searchRequest->getPickupLocationCodeFilter())) {
                    return is_array($filter->getValue()) ?
                        $filter->getValue() : explode(',', $filter->getValue());
                }
            }
        }

        return [];
    }

    /**
     * Sort Sources by Distance.
     *
     * @param array $sources
     * @param array $sourceCodes
     * @param string $sortDirection
     *
     * @return array
     */
    private function sortSourcesByDistance(
        array $sources,
        array $sourceCodes,
        string $sortDirection = SortOrder::SORT_ASC
    ): array {
        if ($sortDirection === SortOrder::SORT_ASC) {
            usort(
                $sources,
                function (SourceInterface $left, SourceInterface $right) use ($sourceCodes) {
                    return array_search(
                            $left->getSourceCode(),
                            $sourceCodes
                        ) <=> array_search(
                            $right->getSourceCode(),
                            $sourceCodes
                        );
                }
            );
        } else {
            usort(
                $sources,
                function (SourceInterface $left, SourceInterface $right) use ($sourceCodes) {
                    return array_search(
                            $right->getSourceCode(),
                            $sourceCodes
                        ) <=> array_search(
                            $left->getSourceCode(),
                            $sourceCodes
                        );
                }
            );
        }

        return $sources;
    }

    /**
     * Check if current Filter is used for Distance Based source codes filtering.
     *
     * @param Filter $filter
     * @param FilterInterface|null $pickupLocationCodeFilter
     *
     * @return bool
     */
    private function isDistanceCodesFilter(Filter $filter, ?FilterInterface $pickupLocationCodeFilter): bool
    {
        return $filter->getField() === SourceInterface::SOURCE_CODE &&
            $filter->getConditionType() === 'eq' &&
            (!$pickupLocationCodeFilter || !$pickupLocationCodeFilter->getValue() === $filter->getValue());
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
            if ($sortOrder->getField() === DistanceFilterInterface::DISTANCE_FIELD) {
                return $sortOrder;
            }
        }

        return null;
    }
}
