<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupApi\Model\SearchResult;

use Magento\InventoryApi\Api\Data\SourceSearchResultsInterface;
use Magento\InventoryInStorePickupApi\Api\Data\SearchRequestInterface;

/**
 * Strategy interface from Pickup Locations extraction.
 * @api
 */
interface StrategyInterface
{
    /**
     * Check if strategy is applicable.
     *
     * @param SearchRequestInterface $searchRequest
     * @param SourceSearchResultsInterface $sourcesSearchResult
     *
     * @return bool
     */
    public function isApplicable(
        SearchRequestInterface $searchRequest,
        SourceSearchResultsInterface $sourcesSearchResult
    ): bool;

    /**
     * Extract Pickup Locations.
     *
     * @param SearchRequestInterface $searchRequest
     * @param SourceSearchResultsInterface $sourcesSearchResult
     *
     * @return array
     */
    public function getSources(
        SearchRequestInterface $searchRequest,
        SourceSearchResultsInterface $sourcesSearchResult
    ): array;
}
