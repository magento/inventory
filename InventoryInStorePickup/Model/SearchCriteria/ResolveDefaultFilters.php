<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model\SearchCriteria;

use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryInStorePickupApi\Api\Data\PickupLocationInterface;
use Magento\InventoryInStorePickupApi\Api\Data\SearchRequestInterface;
use Magento\InventoryInStorePickupApi\Model\SearchCriteria\ResolverInterface;
use Magento\InventoryInStorePickupApi\Model\SearchCriteria\SearchCriteriaBuilderDecorator;

/**
 * Add default filters to the Search Criteria Builder.
 */
class ResolveDefaultFilters implements ResolverInterface
{
    /**
     * @inheritdoc
     */
    public function resolve(
        SearchRequestInterface $searchRequest,
        SearchCriteriaBuilderDecorator $searchCriteriaBuilder
    ): void {
        $searchCriteriaBuilder->addFilter(SourceInterface::ENABLED, "1");
        $searchCriteriaBuilder->addFilter(PickupLocationInterface::IS_PICKUP_LOCATION_ACTIVE, "1");
    }
}
