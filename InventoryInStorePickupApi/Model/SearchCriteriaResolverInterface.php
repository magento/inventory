<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupApi\Model;

use Magento\Framework\Api\SearchCriteria;
use Magento\InventoryInStorePickupApi\Api\Data\SearchRequestInterface;

/**
 * Service to resolve Search Criteria from the Search Request.
 *
 * @api
 */
interface SearchCriteriaResolverInterface
{
    /**
     * @param SearchRequestInterface $searchRequest
     *
     * @return SearchCriteria
     */
    public function resolve(SearchRequestInterface $searchRequest): SearchCriteria;
}
