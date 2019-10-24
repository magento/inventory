<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model\SearchCriteria;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryInStorePickup\Model\ResourceModel\Source\GetCommonSourceCodesBySkus;
use Magento\InventoryInStorePickup\Model\SearchRequest\DistanceFilter\GetDistanceToSources;
use Magento\InventoryInStorePickupApi\Api\Data\SearchRequest\DistanceFilterInterface;
use Magento\InventoryInStorePickupApi\Api\Data\SearchRequestInterface;
use Magento\InventoryInStorePickupApi\Model\SearchCriteria\BuilderPartsResolverInterface;
use Magento\InventoryInStorePickupApi\Model\SearchCriteria\SearchCriteriaBuilderDecorator;

/**
 * Calculate Distance Based Filter and resolve part for Search Criteria Builder.
 *
 * Apply filter by Source Codes, limited by distance, product skus and assignment to the Scope.
 */
class ResolveDistanceFilter implements BuilderPartsResolverInterface
{
    /**
     * @var GetDistanceToSources
     */
    private $getDistanceToSources;

    /**
     * @var GetCommonSourceCodesBySkus
     */
    private $codesBySkus;

    /**
     * @param GetDistanceToSources $getDistanceToSources
     * @param GetCommonSourceCodesBySkus $codesBySkus
     */
    public function __construct(
        GetDistanceToSources $getDistanceToSources,
        GetCommonSourceCodesBySkus $codesBySkus
    ) {
        $this->getDistanceToSources = $getDistanceToSources;
        $this->codesBySkus = $codesBySkus;
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
        $distanceFilter = $searchRequest->getDistanceFilter();
        if (!$distanceFilter) {
            return;
        }
        $codes = $this->getSourceCodes($distanceFilter);
        $codes = implode(',', $codes);
        $searchCriteriaBuilder->addFilter(SourceInterface::SOURCE_CODE, $codes, 'in');
    }

    /**
     * Get Source codes, filtered by Distance and skus.
     *
     * @param DistanceFilterInterface $distanceFilter
     * @return array
     * @throws NoSuchEntityException
     */
    private function getSourceCodes(DistanceFilterInterface $distanceFilter): array
    {
        $skus = $this->getSourceItemsSkus($distanceFilter);
        $sourceCodes = $this->codesBySkus->execute($skus);
        $distances = $this->getDistanceToSources->execute($distanceFilter);

        return array_intersect($sourceCodes, array_keys($distances));
    }

    /**
     * Retrieve source items skus from request.
     *
     * @param DistanceFilterInterface $distanceFilter
     * @return array
     */
    private function getSourceItemsSkus(DistanceFilterInterface $distanceFilter): array
    {
        $skus = [];
        $extensionAttributes = $distanceFilter->getExtensionAttributes();
        if ($extensionAttributes) {
            $skus = $extensionAttributes->getSkus();
        }

        return $skus;
    }
}
