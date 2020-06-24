<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model\SearchCriteria;

use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryInStorePickupApi\Api\Data\PickupLocationInterface;
use Magento\InventoryInStorePickupApi\Api\Data\SearchRequest\FilterInterface;
use Magento\InventoryInStorePickupApi\Api\Data\SearchRequestInterface;
use Magento\InventoryInStorePickupApi\Model\SearchCriteria\ResolverInterface;
use Magento\InventoryInStorePickupApi\Model\SearchCriteria\SearchCriteriaBuilderDecorator;

/**
 * Resolve Search Criteria Builder parts from the Filter Set.
 */
class ResolveFilters implements ResolverInterface
{
    /**
     * @inheritdoc
     */
    public function resolve(
        SearchRequestInterface $searchRequest,
        SearchCriteriaBuilderDecorator $searchCriteriaBuilder
    ): void {
        $filters = $this->extractFilters($searchRequest);

        foreach ($filters as $field => $filter) {
            if ($filter) {
                $searchCriteriaBuilder->addFilter($field, $filter->getValue(), $filter->getConditionType());
            }
        }
    }

    /**
     * Extract filters from Search Request.
     *
     * @param SearchRequestInterface $searchRequest
     *
     * @return FilterInterface[]
     */
    private function extractFilters(SearchRequestInterface $searchRequest): array
    {
        $filterSet = $searchRequest->getFilters();
        if ($filterSet === null) {
            return [];
        }

        return [
            SourceInterface::COUNTRY_ID => $filterSet->getCountry(),
            SourceInterface::REGION_ID => $filterSet->getRegionId(),
            SourceInterface::REGION => $filterSet->getRegion(),
            SourceInterface::POSTCODE => $filterSet->getPostcode(),
            SourceInterface::CITY => $filterSet->getCity(),
            SourceInterface::STREET => $filterSet->getStreet(),
            SourceInterface::SOURCE_CODE => $filterSet->getPickupLocationCode(),
            PickupLocationInterface::FRONTEND_NAME => $filterSet->getName(),
        ];
    }
}
