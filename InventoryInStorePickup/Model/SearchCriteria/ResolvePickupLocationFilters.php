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
 * Add Pickup Location related filters to the Search Criteria.
 */
class ResolvePickupLocationFilters implements BuilderPartsResolverInterface
{
    /**
     * @inheritdoc
     */
    public function resolve(SearchRequestInterface $searchRequest, SearchCriteriaBuilder $searchCriteriaBuilder): void
    {
        if ($searchRequest->getNameFilter()) {
            $searchCriteriaBuilder->addFilter(
                PickupLocationInterface::FRONTEND_NAME,
                $searchRequest->getNameFilter()->getValue(),
                $searchRequest->getNameFilter()->getConditionType()
            );
        }

        if ($searchRequest->getPickupLocationCodeFilter()) {
            $searchCriteriaBuilder->addFilter(
                SourceInterface::SOURCE_CODE,
                $searchRequest->getPickupLocationCodeFilter()->getValue(),
                $searchRequest->getPickupLocationCodeFilter()->getConditionType()
            );
        }

        $searchCriteriaBuilder->addFilter(PickupLocationInterface::IS_PICKUP_LOCATION_ACTIVE, true);
    }
}
