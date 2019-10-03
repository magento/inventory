<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryApi\Api;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\InventoryApi\Api\Data\SourceTypeLinkSearchResultsInterface;

/**
 * Find StockSourceLink list by SearchCriteria API
 *
 * Used fully qualified namespaces in annotations for proper work of WebApi request parser
 *
 * @api
 */
interface GetSourceTypeLinksInterface
{
    /**
     * Find StockSourceLink list by given SearchCriteria
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return SourceTypeLinkSearchResultsInterface
     */
    public function execute(
        SearchCriteriaInterface $searchCriteria
    ): SourceTypeLinkSearchResultsInterface;
}
