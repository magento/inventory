<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model\SearchCriteria;

use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryInStorePickup\Model\ResourceModel\GetPickupLocationIntersectionForSkus;
use Magento\InventoryInStorePickupApi\Api\Data\SearchRequestInterface;
use Magento\InventoryInStorePickupApi\Model\SearchCriteria\BuilderPartsResolverInterface;
use Magento\InventoryInStorePickupApi\Model\SearchCriteria\SearchCriteriaBuilderDecorator;

/**
 * Find intersection of assignments of products between Pickup Locations and
 * add filter by codes which satisfy conditions of the search.
 */
class ResolveIntersection implements BuilderPartsResolverInterface
{
    /**
     * @var GetPickupLocationIntersectionForSkus
     */
    private $getPickupLocationIntersectionForSkus;

    /**
     * @param GetPickupLocationIntersectionForSkus $getPickupLocationIntersectionForSkus
     */
    public function __construct(
        GetPickupLocationIntersectionForSkus $getPickupLocationIntersectionForSkus
    ) {
        $this->getPickupLocationIntersectionForSkus = $getPickupLocationIntersectionForSkus;
    }

    /**
     * Search intersection of assignments of products between Pickup Locations.
     *
     * @param SearchRequestInterface $searchRequest
     * @param SearchCriteriaBuilderDecorator $searchCriteriaBuilder
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function resolve(
        SearchRequestInterface $searchRequest,
        SearchCriteriaBuilderDecorator $searchCriteriaBuilder
    ): void {
        if (!$searchRequest->getFilters()
            || !$searchRequest->getFilters()->getExtensionAttributes()
            || !$searchRequest->getFilters()->getExtensionAttributes()->getProductsInfo()
        ) {
            return;
        }

        $extensionAttributes = $searchRequest->getFilters()->getExtensionAttributes();
        $skus = [];
        foreach ($extensionAttributes->getProductsInfo() as $item) {
            $skus[] = $item->getSku();
        }

        $codes = $this->getPickupLocationIntersectionForSkus->execute($skus);
        $codes = implode(',', $codes);
        $searchCriteriaBuilder->addFilter(SourceInterface::SOURCE_CODE, $codes, 'in');
    }
}
