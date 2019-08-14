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
use Magento\InventoryInStorePickupApi\Api\MapperInterface;
use Magento\InventoryInStorePickupApi\Model\SearchCriteriaResolverInterface;
use Magento\InventoryInStorePickupApi\Model\SearchResult\ExtractStrategyInterface;

/**
 * @inheritdoc
 */
class GetPickupLocations implements GetPickupLocationsInterface
{
    /**
     * @var MapperInterface
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
     * @var ExtractStrategyInterface
     */
    private $extractStrategy;

    /**
     * @var SearchResultInterfaceFactory
     */
    private $searchResultFactory;

    /**
     * @param MapperInterface $mapper
     * @param SourceRepositoryInterface $sourceRepository
     * @param ExtractStrategyInterface $extractStrategy
     * @param SearchCriteriaResolverInterface $searchCriteriaResolver
     * @param SearchResultInterfaceFactory $searchResultFactory
     */
    public function __construct(
        MapperInterface $mapper,
        SourceRepositoryInterface $sourceRepository,
        ExtractStrategyInterface $extractStrategy,
        SearchCriteriaResolverInterface $searchCriteriaResolver,
        SearchResultInterfaceFactory $searchResultFactory
    ) {
        $this->mapper = $mapper;
        $this->sourceRepository = $sourceRepository;
        $this->searchCriteriaResolver = $searchCriteriaResolver;
        $this->extractStrategy = $extractStrategy;
        $this->searchResultFactory = $searchResultFactory;
    }

    /**
     * @inheritdoc
     */
    public function execute(SearchRequestInterface $searchRequest): SearchResultInterface
    {
        $searchCriteria = $this->searchCriteriaResolver->resolve($searchRequest);
        $searchResult = $this->sourceRepository->getList($searchCriteria);

        $sources = $this->extractStrategy->getSources($searchRequest, $searchResult);

        $pickupLocations = [];

        foreach ($sources as $source) {
            $pickupLocations[] = $this->mapper->map($source);
        }

        return $this->searchResultFactory->create(
            [
                'items' => $pickupLocations,
                'totalCount' => count($pickupLocations),
                'searchRequest' => $searchRequest
            ]
        );
    }
}
