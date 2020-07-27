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
use Magento\InventoryInStorePickupApi\Model\SearchCriteria\ResolverInterface;
use Magento\InventoryInStorePickupApi\Model\SearchCriteria\SearchCriteriaBuilderDecorator;

/**
 * Find intersection of assignments of products between Pickup Locations and filter by codes.
 */
class ResolveIntersection implements ResolverInterface
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
        if (!$searchRequest->getExtensionAttributes()
            || !$searchRequest->getExtensionAttributes()->getProductsInfo()
        ) {
            return;
        }

        $extensionAttributes = $searchRequest->getExtensionAttributes();
        $skus = [];
        foreach ($extensionAttributes->getProductsInfo() as $item) {
            if (!in_array($item->getSku(), $skus)) {
                $skus[] = $item->getSku();
            }
        }

        $codes = $this->getPickupLocationIntersectionForSkus->execute($skus);
        $codes = implode(',', $codes);
        $searchCriteriaBuilder->addFilter(SourceInterface::SOURCE_CODE, $codes, 'in');
    }
}
