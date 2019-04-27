<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryApi\Api\Data\StockSourceLinkInterface;
use Magento\InventoryApi\Api\GetStockSourceLinksInterface;
use Magento\InventoryApi\Api\SourceRepositoryInterface;
use Magento\InventoryDistanceBasedSourceSelectionApi\Api\GetLatLngFromAddressInterface;
use Magento\InventoryInStorePickup\Model\Convert\AddressToSourceSelectionAddress;
use Magento\InventoryInStorePickup\Model\PickupLocation\Mapper;
use Magento\InventoryInStorePickup\Model\ResourceModel\Source\GetDistanceOrderedSourceCodes;
use Magento\InventoryInStorePickupApi\Api\Data\AddressInterface;
use Magento\InventoryInStorePickupApi\Api\Data\PickupLocationInterface;
use Magento\InventoryInStorePickupApi\Api\GetNearbyPickupLocationsInterface;

/**
 * Find nearest Pickup Locations by postal code using Haversine formula (Great Circle Distance) database query.
 */
class GetNearbyPickupLocations implements GetNearbyPickupLocationsInterface
{
    /**
     * @var \Magento\InventoryInStorePickup\Model\PickupLocation\Mapper
     */
    private $mapper;

    /**
     * @var \Magento\InventoryInStorePickup\Model\Convert\AddressToSourceSelectionAddress
     */
    private $addressToSourceSelectionAddress;

    /**
     * @var \Magento\InventoryDistanceBasedSourceSelectionApi\Api\GetLatLngFromAddressInterface
     */
    private $getLatLngFromAddress;

    /**
     * @var \Magento\InventoryInStorePickup\Model\ResourceModel\Source\GetDistanceOrderedSourceCodes
     */
    private $getDistanceOrderedSourceCodes;

    /**
     * @var \Magento\InventoryApi\Api\GetStockSourceLinksInterface
     */
    private $getStockSourceLinks;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var \Magento\InventoryApi\Api\SourceRepositoryInterface
     */
    private $sourceRepository;

    /**
     * @param \Magento\InventoryInStorePickup\Model\PickupLocation\Mapper $mapper
     * @param \Magento\InventoryInStorePickup\Model\Convert\AddressToSourceSelectionAddress $addressToSourceSelectionAddress
     * @param \Magento\InventoryDistanceBasedSourceSelectionApi\Api\GetLatLngFromAddressInterface $getLatLngFromAddress
     * @param \Magento\InventoryInStorePickup\Model\ResourceModel\Source\GetDistanceOrderedSourceCodes $getDistanceOrderedSourceCodes
     * @param \Magento\InventoryApi\Api\GetStockSourceLinksInterface $getStockSourceLinks
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Magento\InventoryApi\Api\SourceRepositoryInterface $sourceRepository
     */
    public function __construct(
        Mapper $mapper,
        AddressToSourceSelectionAddress $addressToSourceSelectionAddress,
        GetLatLngFromAddressInterface $getLatLngFromAddress,
        GetDistanceOrderedSourceCodes $getDistanceOrderedSourceCodes,
        GetStockSourceLinksInterface $getStockSourceLinks,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        SourceRepositoryInterface $sourceRepository
    ) {
        $this->mapper = $mapper;
        $this->addressToSourceSelectionAddress = $addressToSourceSelectionAddress;
        $this->getLatLngFromAddress = $getLatLngFromAddress;
        $this->getDistanceOrderedSourceCodes = $getDistanceOrderedSourceCodes;
        $this->getStockSourceLinks = $getStockSourceLinks;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->sourceRepository = $sourceRepository;
    }

    /**
     * @inheritdoc
     */
    public function execute(AddressInterface $address, int $radius, int $stockId): array
    {
        $sourceSelectionAddress = $this->addressToSourceSelectionAddress->execute($address);
        $latLng = $this->getLatLngFromAddress->execute($sourceSelectionAddress);

        $codes = $this->getDistanceOrderedSourceCodes->execute($latLng, $radius);

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(StockSourceLinkInterface::STOCK_ID, $stockId)
            ->addFilter(StockSourceLinkInterface::SOURCE_CODE, $codes, 'in')
            ->create();
        $searchResult = $this->getStockSourceLinks->execute($searchCriteria);
        $stockCodes = [];

        foreach ($searchResult->getItems() as $item) {
            $stockCodes[] = $item->getSourceCode();
        }

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(SourceInterface::SOURCE_CODE, $stockCodes, 'in')
            ->addFilter(PickupLocationInterface::IS_PICKUP_LOCATION_ACTIVE, true)
            ->create();
        $searchResult = $this->sourceRepository->getList($searchCriteria);

        $results = [];

        foreach ($searchResult->getItems() as $source) {
            $results[] = $this->mapper->map($source);
        }

        usort($results, function (PickupLocationInterface $left, PickupLocationInterface $right) use ($codes) {
            return array_search($left->getSourceCode(), $codes) <=> array_search($right->getSourceCode(), $codes);
        });

        return $results;
    }
}
