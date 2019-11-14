<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model;

use Magento\InventoryApi\Api\SourceRepositoryInterface;
use Magento\InventoryInStorePickupApi\Api\Data\SearchRequestInterface;
use Magento\InventoryInStorePickupApi\Api\Data\SearchResultInterface;
use Magento\InventoryInStorePickupApi\Api\Data\SearchResultInterfaceFactory;
use Magento\InventoryInStorePickupApi\Api\GetPickupLocationsInterface;
use Magento\InventoryInStorePickupApi\Model\Mapper;
use Magento\InventoryInStorePickupApi\Model\SearchCriteriaResolverInterface;
use Magento\InventoryInStorePickupApi\Model\SearchResult\ExtractorInterface;

/**
 * @inheritdoc
 */
class GetPickupLocations implements GetPickupLocationsInterface
{
    /**
     * @var Mapper
     */
    private $mapper;

    /**
     * @var SourceRepositoryInterface
     */
    private $sourceRepository;

    /**
     * @var SearchCriteriaResolverInterface
     */
    private $searchCriteriaResolver;

    /**
     * @var ExtractorInterface
     */
    private $extractor;

    /**
     * @var SearchResultInterfaceFactory
     */
    private $searchResultFactory;

    /**
     * @param Mapper $mapper
     * @param SourceRepositoryInterface $sourceRepository
     * @param ExtractorInterface $extractor
     * @param SearchCriteriaResolverInterface $searchCriteriaResolver
     * @param SearchResultInterfaceFactory $searchResultFactory
     */
    public function __construct(
        Mapper $mapper,
        SourceRepositoryInterface $sourceRepository,
        ExtractorInterface $extractor,
        SearchCriteriaResolverInterface $searchCriteriaResolver,
        SearchResultInterfaceFactory $searchResultFactory
    ) {
        $this->mapper = $mapper;
        $this->sourceRepository = $sourceRepository;
        $this->searchCriteriaResolver = $searchCriteriaResolver;
        $this->extractor = $extractor;
        $this->searchResultFactory = $searchResultFactory;
    }

    /**
     * @inheritdoc
     */
    public function execute(SearchRequestInterface $searchRequest): SearchResultInterface
    {
        $searchCriteria = $this->searchCriteriaResolver->resolve($searchRequest);
        $searchResult = $this->sourceRepository->getList($searchCriteria);

        $sources = $this->extractor->getSources($searchRequest, $searchResult);

        $pickupLocations = [];

        foreach ($sources as $source) {
            $pickupLocations[] = $this->mapper->map($source);
        }

        return $this->searchResultFactory->create(
            [
                'items' => $pickupLocations,
                'totalCount' => $searchResult->getTotalCount(),
                'searchRequest' => $searchRequest
            ]
        );
    }
}
