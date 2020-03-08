<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\InventoryInStorePickup\Model;

use Magento\Framework\Api\FilterBuilder;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryInStorePickup\Model\SearchCriteria\ResolveFilters;
use Magento\InventoryInStorePickupApi\Api\Data\SearchRequestInterface;
use Magento\InventoryInStorePickup\Model\ResourceModel\GetPickupLocationIntersectionForSkus;
use Magento\InventoryInStorePickupApi\Model\SearchCriteria\SearchCriteriaBuilderDecorator;
use Magento\InventorySalesApi\Api\StockResolverInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\InventoryApi\Api\Data\StockSourceLinkInterface;
use Magento\InventoryApi\Api\GetStockSourceLinksInterface;

/**
 * Filter pickup locations according to products availability.
 */
class FilterSourcesByProductsAvailabilityPlugin
{
    /**
     * @var GetPickupLocationIntersectionForSkus
     */
    private $getPickupLocationIntersectionForSkues;

    /**
     * @var StockResolverInterface
     */
    private $stockResolver;

    /**
     * @var GetStockSourceLinksInterface
     */
    private $getStockSourceLinks;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var FilterBuilder
     */
    private $filterBuilder;

    /**
     * @param GetPickupLocationIntersectionForSkus $getPickupLocationIntersectionForSkues
     * @param StockResolverInterface $stockResolver
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param GetStockSourceLinksInterface $getStockSourceLinks
     * @param FilterBuilder $filterBuilder
     */
    public function __construct(
        GetPickupLocationIntersectionForSkus $getPickupLocationIntersectionForSkues,
        StockResolverInterface $stockResolver,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        GetStockSourceLinksInterface $getStockSourceLinks,
        FilterBuilder $filterBuilder
    ) {
        $this->getPickupLocationIntersectionForSkues = $getPickupLocationIntersectionForSkues;
        $this->stockResolver = $stockResolver;
        $this->getStockSourceLinks = $getStockSourceLinks;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->filterBuilder = $filterBuilder;
    }

    /**
     * @param ResolveFilters $subject
     * @param $result
     * @param SearchRequestInterface $searchRequest
     * @param SearchCriteriaBuilderDecorator $searchCriteriaBuilder
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @return void
     */
    public function afterResolve(
        ResolveFilters $subject,
        $result,
        SearchRequestInterface $searchRequest,
        SearchCriteriaBuilderDecorator $searchCriteriaBuilder
    ) : void {
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

        $stock = $this->stockResolver->execute($searchRequest->getScopeType(), $searchRequest->getScopeCode());
        $availableSources = array_intersect(
            $this->getPickupLocationIntersectionForSkues->execute($skus),
            $this->getSourceCodesAssignedToStock($stock->getStockId())
        );

        $filter = $this->filterBuilder
            ->setField(SourceInterface::SOURCE_CODE)
            ->setValue($availableSources)
            ->setConditionType('in')
            ->create();
        $searchCriteriaBuilder->addFilters([$filter]);
    }

    /**
     * Get list of Source Codes assigned to the Stock.
     *
     * @param int $stockId
     *
     * @return string[]
     */
    private function getSourceCodesAssignedToStock(int $stockId): array
    {
        $searchCriteriaStockSource = $this->searchCriteriaBuilder
            ->addFilter(StockSourceLinkInterface::STOCK_ID, $stockId)
            ->create();
        $searchResult = $this->getStockSourceLinks->execute($searchCriteriaStockSource);
        $codes = [];
        foreach ($searchResult->getItems() as $item) {
            $codes[] = $item->getSourceCode();
        }

        return $codes;
    }
}
