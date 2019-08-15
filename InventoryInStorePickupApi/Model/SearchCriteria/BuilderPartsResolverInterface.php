<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupApi\Model\SearchCriteria;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\InventoryInStorePickupApi\Api\Data\SearchRequestInterface;

/**
 * Resolve Search Request into Search Criteria Builder parts.
 *
 * @api
 */
interface BuilderPartsResolverInterface
{
    /**
     * Resolve Search Request and place it into Search Criteria Builder parts.
     *
     * @param SearchRequestInterface $searchRequest
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function resolve(SearchRequestInterface $searchRequest, SearchCriteriaBuilder $searchCriteriaBuilder): void;
}
