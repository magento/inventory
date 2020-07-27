<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model\SearchCriteria;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryInStorePickup\Model\SearchRequest\Area\GetDistanceToSources;
use Magento\InventoryInStorePickupApi\Api\Data\SearchRequestInterface;
use Magento\InventoryInStorePickupApi\Model\SearchCriteria\ResolverInterface;
use Magento\InventoryInStorePickupApi\Model\SearchCriteria\SearchCriteriaBuilderDecorator;

/**
 * Calculate Distance Based Filter and resolve part for Search Criteria Builder.
 *
 * Apply filter by Source Codes, limited by distance and assignment to the Scope.
 */
class ResolveArea implements ResolverInterface
{
    /**
     * @var GetDistanceToSources
     */
    private $getDistanceToSources;

    /**
     * @param GetDistanceToSources $getDistanceToSources
     */
    public function __construct(
        GetDistanceToSources $getDistanceToSources
    ) {
        $this->getDistanceToSources = $getDistanceToSources;
    }

    /**
     * @inheritdoc
     *
     * @throws NoSuchEntityException
     */
    public function resolve(
        SearchRequestInterface $searchRequest,
        SearchCriteriaBuilderDecorator $searchCriteriaBuilder
    ): void {
        $codes = $this->getSourceCodes($searchRequest);

        if ($codes !== null) {
            $codes = implode(',', $codes);
            $searchCriteriaBuilder->addFilter(SourceInterface::SOURCE_CODE, $codes, 'in');
        }
    }

    /**
     * Get Source codes, filtered by Distance.
     *
     * @param SearchRequestInterface $searchRequest
     *
     * @return array|null
     * @throws NoSuchEntityException
     */
    private function getSourceCodes(SearchRequestInterface $searchRequest): ?array
    {
        $area = $searchRequest->getArea();

        if ($area === null) {
            return null;
        }

        $distances = $this->getDistanceToSources->execute($area);

        return array_keys($distances);
    }
}
