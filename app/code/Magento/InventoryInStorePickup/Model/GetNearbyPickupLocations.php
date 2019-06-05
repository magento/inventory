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
use Magento\InventoryInStorePickup\Model\ResourceModel\Source\GetDistanceOrderedSourceCodes;
use Magento\InventoryInStorePickupApi\Api\Data\AddressInterface;
use Magento\InventoryInStorePickupApi\Api\Data\PickupLocationInterface;
use Magento\InventoryInStorePickupApi\Api\GetNearbyPickupLocationsInterface;
use Magento\InventoryInStorePickupApi\Model\Mapper;
use Magento\InventorySalesApi\Api\StockResolverInterface;

/**
 * @inheritdoc
 */
class GetNearbyPickupLocations implements GetNearbyPickupLocationsInterface
{
    /**
     * @var Mapper
     */
    private $mapper;

    /**
     * @var AddressToSourceSelectionAddress
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
     * @param Mapper $mapper
     * @param AddressToSourceSelectionAddress $addressToSourceSelectionAddress
     * @param GetLatLngFromAddressInterface $getLatLngFromAddress
     * @param GetDistanceOrderedSourceCodes $getDistanceOrderedSourceCodes
     * @param GetStockSourceLinksInterface $getStockSourceLinks
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param SourceRepositoryInterface $sourceRepository
     * @param StockResolverInterface $stockResolver
     */
    public function __construct(
        Mapper $mapper,
        AddressToSourceSelectionAddress $addressToSourceSelectionAddress,
        GetLatLngFromAddressInterface $getLatLngFromAddress,
        GetDistanceOrderedSourceCodes $getDistanceOrderedSourceCodes,
        GetStockSourceLinksInterface $getStockSourceLinks,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        SourceRepositoryInterface $sourceRepository,
        StockResolverInterface $stockResolver
    ) {
        $this->mapper = $mapper;
        $this->addressToSourceSelectionAddress = $addressToSourceSelectionAddress;
        $this->getLatLngFromAddress = $getLatLngFromAddress;
        $this->getDistanceOrderedSourceCodes = $getDistanceOrderedSourceCodes;
        $this->getStockSourceLinks = $getStockSourceLinks;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->sourceRepository = $sourceRepository;
        $this->stockResolver = $stockResolver;
    }

    /**
     * @inheritdoc
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute(
        AddressInterface $address,
        int $radius,
        string $salesChannelType,
        string $salesChannelCode
    ): array {
        $sourceSelectionAddress = $this->addressToSourceSelectionAddress->execute($address);
        $latLng = $this->getLatLngFromAddress->execute($sourceSelectionAddress);

        $codes = $this->getDistanceOrderedSourceCodes->execute($latLng, $radius);

        $stock = $this->stockResolver->execute($salesChannelType, $salesChannelCode);
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(StockSourceLinkInterface::STOCK_ID, $stock->getStockId())
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
