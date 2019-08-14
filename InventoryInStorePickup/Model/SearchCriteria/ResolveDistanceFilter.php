<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model\SearchCriteria;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryApi\Api\Data\StockSourceLinkInterface;
use Magento\InventoryApi\Api\GetStockSourceLinksInterface;
use Magento\InventoryDistanceBasedSourceSelectionApi\Api\GetLatLngFromAddressInterface;
use Magento\InventoryInStorePickup\Model\ResourceModel\Source\GetDistanceOrderedSourceCodes;
use Magento\InventoryInStorePickup\Model\SearchRequest\ConvertToSourceSelectionAddress;
use Magento\InventoryInStorePickupApi\Api\Data\SearchRequest\DistanceFilterInterface;
use Magento\InventoryInStorePickupApi\Api\Data\SearchRequestInterface;
use Magento\InventoryInStorePickupApi\Model\BuilderPartsResolverInterface;
use Magento\InventorySalesApi\Api\StockResolverInterface;

/**
 * Calculate Distance Based Filter and resolve part for Search Criteria Builder.
 */
class ResolveDistanceFilter implements BuilderPartsResolverInterface
{
    /**
     * @var ConvertToSourceSelectionAddress
     */
    private $convertToSourceSelectionAddress;

    /**
     * @var GetLatLngFromAddressInterface
     */
    private $getLatLngFromAddress;

    /**
     * @var GetDistanceOrderedSourceCodes
     */
    private $getDistanceOrderedSourceCodes;

    /**
     * @var StockResolverInterface
     */
    private $stockResolver;

    /**
     * @var SearchCriteriaBuilderFactory
     */
    private $searchCriteriaBuilderFactory;

    /**
     * @var GetStockSourceLinksInterface
     */
    private $getStockSourceLinks;

    /**
     * @param ConvertToSourceSelectionAddress $convertToSourceSelectionAddress
     * @param GetLatLngFromAddressInterface $getLatLngFromAddress
     * @param GetDistanceOrderedSourceCodes $getDistanceOrderedSourceCodes
     * @param StockResolverInterface $stockResolver
     * @param SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
     * @param GetStockSourceLinksInterface $getStockSourceLinks
     */
    public function __construct(
        ConvertToSourceSelectionAddress $convertToSourceSelectionAddress,
        GetLatLngFromAddressInterface $getLatLngFromAddress,
        GetDistanceOrderedSourceCodes $getDistanceOrderedSourceCodes,
        StockResolverInterface $stockResolver,
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        GetStockSourceLinksInterface $getStockSourceLinks
    ) {
        $this->convertToSourceSelectionAddress = $convertToSourceSelectionAddress;
        $this->getLatLngFromAddress = $getLatLngFromAddress;
        $this->getDistanceOrderedSourceCodes = $getDistanceOrderedSourceCodes;
        $this->stockResolver = $stockResolver;
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
        $this->getStockSourceLinks = $getStockSourceLinks;
    }

    /**
     * @inheritdoc
     * @throws NoSuchEntityException
     */
    public function resolve(SearchRequestInterface $searchRequest, SearchCriteriaBuilder $searchCriteriaBuilder): void
    {
        $codes = $this->getSourceCodes($searchRequest);

        if ($codes !== null) {
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
        $distanceFilter = $searchRequest->getDistanceFilter();

        if ($distanceFilter === null) {
            return null;
        }

        $codes = $this->getCodesOfClosestSources($distanceFilter);
        $stockId = $this->getStockId($searchRequest);

        return $this->filterSourcesByStockId($codes, $stockId);
    }

    /**
     * Filter list of codes by assignment to Stock.
     *
     * @param array $sourceCodes
     * @param int $stockId
     *
     * @return string[]
     */
    private function filterSourcesByStockId(array $sourceCodes, int $stockId): array
    {
        $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();
        $searchCriteriaStockSource = $searchCriteriaBuilder
            ->addFilter(StockSourceLinkInterface::STOCK_ID, $stockId)
            ->addFilter(StockSourceLinkInterface::SOURCE_CODE, $sourceCodes, 'in')
            ->create();

        $searchResult = $this->getStockSourceLinks->execute($searchCriteriaStockSource);

        $codes = [];
        foreach ($searchResult->getItems() as $item) {
            $codes[] = $item->getSourceCode();
        }

        return $codes;
    }

    /**
     * Get Stock Id from Search Request.
     *
     * @param SearchRequestInterface $searchRequest
     *
     * @return int
     * @throws NoSuchEntityException
     */
    private function getStockId(SearchRequestInterface $searchRequest): int
    {
        $scopeType = $searchRequest->getScopeType();
        $scopeCode = $searchRequest->getScopeCode();

        $stock = $this->stockResolver->execute($scopeType, $scopeCode);

        return $stock->getStockId();
    }

    /**
     * Get codes closest sources to requested address.
     *
     * @param DistanceFilterInterface $distanceFilter
     *
     * @return string[]
     */
    private function getCodesOfClosestSources(DistanceFilterInterface $distanceFilter): array
    {
        $sourceSelectionAddress = $this->convertToSourceSelectionAddress->execute($distanceFilter);
        $latLng = $this->getLatLngFromAddress->execute($sourceSelectionAddress);

        return $this->getDistanceOrderedSourceCodes->execute($latLng, $distanceFilter->getRadius());
    }
}
