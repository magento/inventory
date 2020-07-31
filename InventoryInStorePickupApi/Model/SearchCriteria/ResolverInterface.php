<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupApi\Model\SearchCriteria;

use Magento\InventoryInStorePickupApi\Api\Data\SearchRequestInterface;

/**
 * Resolve Search Request into framework Search Criteria Builder parameters.
 *
 * @api
 */
interface ResolverInterface
{
    /**
     * Resolve Search Request and place it into Search Criteria Builder parts.
     *
     * @param SearchRequestInterface $searchRequest
     * @param SearchCriteriaBuilderDecorator $searchCriteriaBuilder
     */
    public function resolve(
        SearchRequestInterface $searchRequest,
        SearchCriteriaBuilderDecorator $searchCriteriaBuilder
    ): void;
}
