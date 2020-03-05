<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\InventoryInStorePickupShipping\Model;

use Magento\InventoryInStorePickupApi\Api\Data\SearchRequestInterface;
use Magento\InventoryInStorePickupApi\Api\Data\SearchResultInterface;
use Magento\InventoryInStorePickupApi\Api\Data\SearchResultInterfaceFactory;
use Magento\InventoryInStorePickupApi\Api\GetPickupLocationsInterface;
use Magento\InventoryInStorePickupShipping\Model\ResourceModel\GetPickupLocationIntersectionForSkus;
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
     * @var SearchResultInterfaceFactory
     */
    private $searchResultFactory;

    /**
     * @param GetPickupLocationIntersectionForSkus $getPickupLocationIntersectionForSkues
     * @param StockResolverInterface $stockResolver
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param GetStockSourceLinksInterface $getStockSourceLinks
     * @param SearchResultInterfaceFactory $searchResultFactory
     */
    public function __construct(
        GetPickupLocationIntersectionForSkus $getPickupLocationIntersectionForSkues,
        StockResolverInterface $stockResolver,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        GetStockSourceLinksInterface $getStockSourceLinks,
        SearchResultInterfaceFactory $searchResultFactory
    ) {
        $this->getPickupLocationIntersectionForSkues = $getPickupLocationIntersectionForSkues;
        $this->stockResolver = $stockResolver;
        $this->getStockSourceLinks = $getStockSourceLinks;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->searchResultFactory = $searchResultFactory;
    }

    /**
     * @param GetPickupLocationsInterface $subject
     * @param SearchResultInterface $result
     * @param SearchRequestInterface $request
     * @return SearchResultInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function afterExecute(
        GetPickupLocationsInterface $subject,
        SearchResultInterface $result,
        SearchRequestInterface $request
    ) : SearchResultInterface {
        $extensionAttributes = $request->getExtensionAttributes();
        if (!$extensionAttributes || !$extensionAttributes->getProductsInfo()) {
            return $result;
        }

        $skus = [];
        foreach ($extensionAttributes->getProductsInfo() as $item) {
            $skus[] = $item->getSku();
        }

        $stock = $this->stockResolver->execute($request->getScopeType(), $request->getScopeCode());
        $availableSources = array_intersect(
            $this->getPickupLocationIntersectionForSkues->execute($skus),
            $this->getSourceCodesAssignedToStock($stock->getStockId())
        );

        $availableLocations = [];
        foreach ($result->getItems() as $pickupLocation) {
            if (in_array($pickupLocation->getPickupLocationCode(), $availableSources)) {
                $availableLocations[] = $pickupLocation;
            }
        }

        return $this->searchResultFactory->create(
            [
                'items' => $availableLocations,
                'totalCount' => count($availableLocations),
                'searchRequest' => $request
            ]
        );
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
