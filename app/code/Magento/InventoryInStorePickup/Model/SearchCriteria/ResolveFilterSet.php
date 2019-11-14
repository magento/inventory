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
use Magento\InventoryInStorePickupApi\Model\SearchCriteria\BuilderPartsResolverInterface;
use Magento\InventoryInStorePickupApi\Model\SearchCriteria\SearchCriteriaBuilderDecorator;

/**
 * Resolve Search Criteria Builder parts from the Filter Set.
 */
class ResolveFilterSet implements BuilderPartsResolverInterface
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
        $filters = [];

        $filterSet = $searchRequest->getFilterSet();

        if ($filterSet === null) {
            return $filters;
        }

        $filters[SourceInterface::COUNTRY_ID] = $filterSet->getCountryFilter();
        $filters[SourceInterface::REGION] = $filterSet->getRegionFilter();
        $filters[SourceInterface::REGION_ID] = $filterSet->getRegionIdFilter();
        $filters[SourceInterface::POSTCODE] = $filterSet->getPostcodeFilter();
        $filters[SourceInterface::CITY] = $filterSet->getCityFilter();
        $filters[SourceInterface::STREET] = $filterSet->getStreetFilter();
        $filters[SourceInterface::SOURCE_CODE] = $filterSet->getPickupLocationCodeFilter();
        $filters[PickupLocationInterface::FRONTEND_NAME] = $filterSet->getNameFilter();

        return $filters;
    }
}
