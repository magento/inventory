<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model\SearchCriteria;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryInStorePickupApi\Api\Data\SearchRequest\FilterInterface;
use Magento\InventoryInStorePickupApi\Api\Data\SearchRequestInterface;
use Magento\InventoryInStorePickupApi\Model\BuilderPartsResolverInterface;

/**
 * Resolve Search Criteria Builder parts from Address Filter.
 */
class ResolveAddressFilter implements BuilderPartsResolverInterface
{
    /**
     * @inheritdoc
     */
    public function resolve(SearchRequestInterface $searchRequest, SearchCriteriaBuilder $searchCriteriaBuilder): void
    {
        $filters = $this->extractAddressFitlers($searchRequest);

        foreach ($filters as $field => $filter) {
            $searchCriteriaBuilder->addFilter($field, $filter->getValue(), $filter->getConditionType());
        }
    }

    /**
     * Extract Address filters from Search Request.
     *
     * @param SearchRequestInterface $searchRequest
     *
     * @return FilterInterface[]
     */
    private function extractAddressFitlers(SearchRequestInterface $searchRequest): array
    {
        $filters = [];

        $addressFilter = $searchRequest->getAddressFilter();

        if ($addressFilter === null) {
            return $filters;
        }

        $filters[SourceInterface::COUNTRY_ID] = $addressFilter->getCountryFilter();
        $filters[SourceInterface::REGION] = $addressFilter->getRegionFilter();
        $filters[SourceInterface::REGION_ID] = $addressFilter->getRegionIdFilter();
        $filters[SourceInterface::CITY] = $addressFilter->getCityFilter();
        $filters[SourceInterface::STREET] = $addressFilter->getStreetFilter();

        return $filters;
    }
}
