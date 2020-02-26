<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupShipping\Model;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\InventoryApi\Api\Data\StockSourceLinkInterface;
use Magento\InventoryApi\Api\GetStockSourceLinksInterface;
use Magento\InventoryApi\Api\SourceRepositoryInterface;
use Magento\InventoryInStorePickupShipping\Model\ResourceModel\GetPickupLocationIntersectionForSkus;
use Magento\InventoryInStorePickupShippingApi\Api\Data\RequestInterface;
use Magento\InventoryInStorePickupShippingApi\Api\GetAvailableLocationsForPickupInterface;
use Magento\InventorySalesApi\Api\StockResolverInterface;

/**
 * @inheritdoc
 */
class GetAvailableLocationsForPickup implements GetAvailableLocationsForPickupInterface
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
     * @var SourceRepositoryInterface
     */
    private $sourceRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @param GetPickupLocationIntersectionForSkus $getPickupLocationIntersectionForSkues
     * @param StockResolverInterface $stockResolver
     * @param GetStockSourceLinksInterface $getStockSourceLinks
     * @param SourceRepositoryInterface $sourceRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        GetPickupLocationIntersectionForSkus $getPickupLocationIntersectionForSkues,
        StockResolverInterface $stockResolver,
        GetStockSourceLinksInterface $getStockSourceLinks,
        SourceRepositoryInterface $sourceRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->getPickupLocationIntersectionForSkues = $getPickupLocationIntersectionForSkues;
        $this->stockResolver = $stockResolver;
        $this->getStockSourceLinks = $getStockSourceLinks;
        $this->sourceRepository = $sourceRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * @inheritdoc
     */
    public function execute(RequestInterface $request): array
    {
        $skus = [];
        foreach ($request->getProductsInfo() as $item) {
            $skus[] = $item->getSku();
        }
        $sourceCodes = $this->getPickupLocationIntersectionForSkues->execute($skus);
        $stock = $this->stockResolver->execute($request->getScopeType(), $request->getScopeCode());
        $sourceCodesAssignedToStock = $this->getSourceCodesAssignedToStock($stock->getStockId());

        return array_intersect($sourceCodes, $sourceCodesAssignedToStock);
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
