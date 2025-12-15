<?php
/**
 * Copyright 2017 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\Inventory\Model;

use Magento\Framework\Api\SearchResults;
use Magento\InventoryApi\Api\Data\SourceSearchResultsInterface;

/**
 * Represents the search results for inventory sources,
 * extending the base `SearchResults` class and implementing the `SourceSearchResultsInterface`.
 */
class SourceSearchResults extends SearchResults implements SourceSearchResultsInterface
{
}
