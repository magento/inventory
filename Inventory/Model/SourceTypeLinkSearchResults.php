<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Inventory\Model;

use Magento\Framework\Api\SearchResults;
use Magento\InventoryApi\Api\Data\SourceTypeLinkSearchResultsInterface;

/**
 * Service Data Object with Source Type Link search results.
 */
class SourceTypeLinkSearchResults extends SearchResults implements SourceTypeLinkSearchResultsInterface
{
}
