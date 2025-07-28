<?php
/**
 * Copyright 2017 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\Inventory\Model;

use Magento\Framework\Api\SearchResults;
use Magento\InventoryApi\Api\Data\SourceItemSearchResultsInterface;

/**
 * Represents the search results for source items,
 * extending the base `SearchResults` class and implementing the `SourceItemSearchResultsInterface`.
 */
class SourceItemSearchResults extends SearchResults implements SourceItemSearchResultsInterface
{
}
