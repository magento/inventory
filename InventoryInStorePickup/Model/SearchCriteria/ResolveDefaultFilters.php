<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model\SearchCriteria;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryInStorePickupApi\Api\Data\PickupLocationInterface;
use Magento\InventoryInStorePickupApi\Api\Data\SearchRequestInterface;
use Magento\InventoryInStorePickupApi\Model\SearchCriteria\BuilderPartsResolverInterface;

/**
 * Add default filters to the Search Criteria Builder.
 */
class ResolveDefaultFilters implements BuilderPartsResolverInterface
{
    /**
     * @inheritdoc
     */
    public function resolve(SearchRequestInterface $searchRequest, SearchCriteriaBuilder $searchCriteriaBuilder): void
    {
        $searchCriteriaBuilder->addFilter(SourceInterface::ENABLED, true);
        $searchCriteriaBuilder->addFilter(PickupLocationInterface::IS_PICKUP_LOCATION_ACTIVE, true);
    }
}
