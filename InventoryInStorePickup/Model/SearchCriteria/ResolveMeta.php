<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model\SearchCriteria;

use Magento\Framework\Exception\InputException;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryInStorePickupApi\Api\Data\PickupLocationInterface;
use Magento\InventoryInStorePickupApi\Api\Data\SearchRequest\AreaInterface;
use Magento\InventoryInStorePickupApi\Api\Data\SearchRequestInterface;
use Magento\InventoryInStorePickupApi\Model\SearchCriteria\ResolverInterface;
use Magento\InventoryInStorePickupApi\Model\SearchCriteria\SearchCriteriaBuilderDecorator;

/**
 * Resolve Page and Sort related information for the Search Criteria Builder.
 */
class ResolveMeta implements ResolverInterface
{
    /**
     * @var array
     */
    private $translations = [
        SourceInterface::NAME => PickupLocationInterface::FRONTEND_NAME,
        PickupLocationInterface::PICKUP_LOCATION_CODE => SourceInterface::SOURCE_CODE,
        SourceInterface::DESCRIPTION => PickupLocationInterface::FRONTEND_DESCRIPTION
    ];

    /**
     * @inheritdoc
     *
     * @throws InputException
     */
    public function resolve(
        SearchRequestInterface $searchRequest,
        SearchCriteriaBuilderDecorator $searchCriteriaBuilder
    ): void {
        $searchCriteriaBuilder->setCurrentPage($searchRequest->getCurrentPage());

        if ($searchRequest->getPageSize()) {
            $searchCriteriaBuilder->setPageSize($searchRequest->getPageSize());
        }

        if ($searchRequest->getSort()) {
            $this->addSortOrders($searchRequest, $searchCriteriaBuilder);
        }
    }

    /**
     * Adjust and add Sort Orders to the Search Criteria Builder.
     *
     * @param SearchRequestInterface $searchRequest
     * @param SearchCriteriaBuilderDecorator $searchCriteriaBuilder
     *
     * @throws InputException
     */
    private function addSortOrders(
        SearchRequestInterface $searchRequest,
        SearchCriteriaBuilderDecorator $searchCriteriaBuilder
    ): void {
        $sorts = [];
        foreach ($searchRequest->getSort() as $sortOrder) {
            if ($sortOrder->getField() === AreaInterface::DISTANCE_FIELD) {
                // If sort order need to be done by distance, not other sort orders are allowed.
                if ($searchRequest->getArea()) {
                    return;
                }
                // Sort Order by 'distance' must be skipped in case that Distance Filter is missed.
                continue;
            }

            $sortOrder->setField($this->translateFieldName($sortOrder->getField()));

            $sorts[] = $sortOrder;
        }

        $searchCriteriaBuilder->setSortOrders($sorts);
    }

    /**
     * Translate field name according to possible difference due projection.
     *
     * @param string $requestedName
     *
     * @return string
     */
    private function translateFieldName(string $requestedName): string
    {
        return $this->translations[$requestedName] ?? $requestedName;
    }
}
