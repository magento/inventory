<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model\SearchCriteria;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\InputException;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryInStorePickupApi\Api\Data\PickupLocationInterface;
use Magento\InventoryInStorePickupApi\Api\Data\SearchRequest\DistanceFilterInterface;
use Magento\InventoryInStorePickupApi\Api\Data\SearchRequestInterface;

/**
 * Resolve Page and Sort related information for Search Criteria Builder.
 */
class ResolveSearchRequestMeta implements \Magento\InventoryInStorePickupApi\Model\BuilderPartsResolverInterface
{
    private const PICKUP_LOCATION_CODE = 'pickup_location_code';

    /**
     * @inheritdoc
     *
     * @throws InputException
     */
    public function resolve(SearchRequestInterface $searchRequest, SearchCriteriaBuilder $searchCriteriaBuilder): void
    {
        $this->addPageDetails($searchRequest, $searchCriteriaBuilder);
        $this->addSortOrders($searchRequest, $searchCriteriaBuilder);
    }

    /**
     * Adjust and add Sort Orders to the Search Criteria Builder.
     *
     * @param SearchRequestInterface $searchRequest
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     *
     * @throws InputException
     */
    private function addSortOrders(
        SearchRequestInterface $searchRequest,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ): void {

        $sorts = [];
        foreach ($searchRequest->getSort() as $sortOrder) {
            if ($sortOrder->getField() === DistanceFilterInterface::DISTANCE_FIELD) {
                // If sort order need to be done by distance, not other sort orders are allowed.
                if ($searchRequest->getDistanceFilter()) {
                    return;
                }
                // Sort order should be skipped in case that Distance Filter is missed.
                continue;
            }

            if ($sortOrder->getField() === SourceInterface::NAME) {
                $sortOrder->setField(PickupLocationInterface::FRONTEND_NAME);
            }

            if ($sortOrder->getField() === self::PICKUP_LOCATION_CODE) {
                $sortOrder->setField(SourceInterface::SOURCE_CODE);
            }

            $sorts[] = $sortOrder;
        }

        $searchCriteriaBuilder->setSortOrders($sorts);
    }

    /**
     * Add Page Details to Search Criteria Builder.
     *
     * @param SearchRequestInterface $searchRequest
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    private function addPageDetails(
        SearchRequestInterface $searchRequest,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ): void {
        if ($searchRequest->getPageSize()) {
            $searchCriteriaBuilder->setPageSize($searchRequest->getPageSize());
        }

        $searchCriteriaBuilder->setCurrentPage($searchRequest->getCurrentPage());
    }
}
