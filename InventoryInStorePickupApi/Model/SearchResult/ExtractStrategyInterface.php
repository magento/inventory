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
 * Service to determine strategy and extract Pickup Locations from the Search Result.
 * @api
 */
interface ExtractStrategyInterface
{
    /**
     * Extract Pickup Location according to the strategy.
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
