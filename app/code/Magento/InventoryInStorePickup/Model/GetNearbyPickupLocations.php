<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryApi\Api\Data\StockSourceLinkInterface;
use Magento\InventoryApi\Api\GetStockSourceLinksInterface;
use Magento\InventoryApi\Api\SourceRepositoryInterface;
use Magento\InventoryDistanceBasedSourceSelectionApi\Api\GetLatLngFromAddressInterface;
use Magento\InventoryInStorePickup\Model\Convert\ToSourceSelectionAddress;
use Magento\InventoryInStorePickup\Model\ResourceModel\Source\GetDistanceOrderedSourceCodes;
use Magento\InventoryInStorePickupApi\Api\Data\PickupLocationInterface;
use Magento\InventoryInStorePickupApi\Api\Data\SearchCriteria\GetNearbyLocationsCriteriaInterface;
use Magento\InventoryInStorePickupApi\Api\Data\SearchResultInterface;
use Magento\InventoryInStorePickupApi\Api\Data\SearchResultInterfaceFactory;
use Magento\InventoryInStorePickupApi\Api\GetNearbyPickupLocationsInterface;
use Magento\InventoryInStorePickupApi\Api\MapperInterface;
use Magento\InventorySalesApi\Api\StockResolverInterface;

/**
 * @inheritdoc
 */
class GetNearbyPickupLocations implements GetNearbyPickupLocationsInterface
{
    /**
     * @var MapperInterface
     */
    private $mapper;

    /**
     * @var ToSourceSelectionAddress
     */
    private $addressToSourceSelectionAddress;

    /**
     * @var GetLatLngFromAddressInterface
     */
    private $getLatLngFromAddress;

    /**
     * @var GetDistanceOrderedSourceCodes
     */
    private $getDistanceOrderedSourceCodes;

    /**
     * @var GetStockSourceLinksInterface
     */
    private $getStockSourceLinks;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var SourceRepositoryInterface
     */
    private $sourceRepository;

    /**
     * @var StockResolverInterface
     */
    private $stockResolver;

    /**
     * @var SearchResultInterfaceFactory
     */
    private $searchResultFactory;

    /**
     * @param MapperInterface $mapper
     * @param ToSourceSelectionAddress $addressToSourceSelectionAddress
     * @param GetLatLngFromAddressInterface $getLatLngFromAddress
     * @param GetDistanceOrderedSourceCodes $getDistanceOrderedSourceCodes
     * @param GetStockSourceLinksInterface $getStockSourceLinks
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param SourceRepositoryInterface $sourceRepository
     * @param StockResolverInterface $stockResolver
     * @param SearchResultInterfaceFactory $searchResultFactory
     */
    public function __construct(
        MapperInterface $mapper,
        ToSourceSelectionAddress $addressToSourceSelectionAddress,
        GetLatLngFromAddressInterface $getLatLngFromAddress,
        GetDistanceOrderedSourceCodes $getDistanceOrderedSourceCodes,
        GetStockSourceLinksInterface $getStockSourceLinks,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        SourceRepositoryInterface $sourceRepository,
        StockResolverInterface $stockResolver,
        SearchResultInterfaceFactory $searchResultFactory
    ) {
        $this->mapper = $mapper;
        $this->addressToSourceSelectionAddress = $addressToSourceSelectionAddress;
        $this->getLatLngFromAddress = $getLatLngFromAddress;
        $this->getDistanceOrderedSourceCodes = $getDistanceOrderedSourceCodes;
        $this->getStockSourceLinks = $getStockSourceLinks;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->sourceRepository = $sourceRepository;
        $this->stockResolver = $stockResolver;
        $this->searchResultFactory = $searchResultFactory;
    }

    /**
     * @inheritdoc
     *
     * @throws NoSuchEntityException
     */
    public function execute(
        GetNearbyLocationsCriteriaInterface $searchCriteria,
        string $salesChannelType,
        string $salesChannelCode
    ): SearchResultInterface {
        $sourceSelectionAddress = $this->addressToSourceSelectionAddress->execute($searchCriteria);
        $latLng = $this->getLatLngFromAddress->execute($sourceSelectionAddress);

        $codes = $this->getDistanceOrderedSourceCodes->execute($latLng, $searchCriteria->getRadius());

        $stock = $this->stockResolver->execute($salesChannelType, $salesChannelCode);
        $searchCriteriaStockSource = $this->searchCriteriaBuilder
            ->addFilter(StockSourceLinkInterface::STOCK_ID, $stock->getStockId())
            ->addFilter(StockSourceLinkInterface::SOURCE_CODE, $codes, 'in')
            ->create();
        $searchResult = $this->getStockSourceLinks->execute($searchCriteriaStockSource);
        $stockCodes = [];

        foreach ($searchResult->getItems() as $item) {
            $stockCodes[] = $item->getSourceCode();
        }

        $searchCriteriaSource = $this->searchCriteriaBuilder
            ->setPageSize($searchCriteria->getPageSize() ?? false)
            ->setCurrentPage($searchCriteria->getCurrentPage() ?? 1)
            ->addFilter(SourceInterface::SOURCE_CODE, $stockCodes, 'in')
            ->addFilter(PickupLocationInterface::IS_PICKUP_LOCATION_ACTIVE, true)
            ->create();
        $searchResult = $this->sourceRepository->getList($searchCriteriaSource);

        $items = [];

        foreach ($searchResult->getItems() as $source) {
            $items[] = $this->mapper->map($source);
        }

        $this->sortByDistance($items, $codes);

        return $this->searchResultFactory->create(
            [
                'items' => $items,
                'totalCount' => count($items),
                'searchCriteria' => $searchCriteria
            ]
        );
    }

    /**
     * Sort pickup locations by distance
     *
     * @param PickupLocationInterface[] $pickupLocations
     * @param string[] $sortedCodes
     *
     * @return void
     */
    private function sortByDistance(&$pickupLocations, array $sortedCodes): void
    {
        usort(
            $pickupLocations,
            function (PickupLocationInterface $left, PickupLocationInterface $right) use ($sortedCodes) {
                return array_search(
                    $left->getSourceCode(),
                    $sortedCodes
                ) <=> array_search(
                    $right->getSourceCode(),
                    $sortedCodes
                );
            }
        );
    }
}
