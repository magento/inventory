<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupGraphQl\Model\Resolver\PickupLocations;

use Magento\InventoryInStorePickupApi\Model\SearchRequestBuilderInterface;
use Magento\InventoryInStorePickupApi\Model\SearchRequestBuilderInterfaceFactory;
use Magento\InventoryInStorePickupGraphQl\Model\Resolver\PickupLocations\SearchRequest\Area;
use Magento\InventoryInStorePickupGraphQl\Model\Resolver\PickupLocations\SearchRequest\CurrentPage;
use Magento\InventoryInStorePickupGraphQl\Model\Resolver\PickupLocations\SearchRequest\Filter;
use Magento\InventoryInStorePickupGraphQl\Model\Resolver\PickupLocations\SearchRequest\PageSize;
use Magento\InventoryInStorePickupGraphQl\Model\Resolver\PickupLocations\SearchRequest\ResolverInterface;
use Magento\InventoryInStorePickupGraphQl\Model\Resolver\PickupLocations\SearchRequest\Sort;
use Magento\InventoryInStorePickupGraphQl\Model\Resolver\PickupLocations\SearchRequestBuilder\ExtensionProvider;

/**
 * Resolve parameters for the Search Request Builder.
 */
class SearchRequestBuilder
{
    private const SORT_FIELD = 'sort';
    private const AREA_FIELD = 'area';
    private const FILTERS_FIELD = 'filters';
    private const CURRENT_PAGE = 'currentPage';
    private const PAGE_SIZE = 'pageSize';

    /**
     * @var ExtensionProvider
     */
    private $extensionProvider;

    /**
     * @var SearchRequestBuilderInterfaceFactory
     */
    private $searchRequestBuilderFactory;

    /**
     * @var ResolverInterface[]
     */
    private $resolvers;

    /**
     * @param ExtensionProvider $extensionProvider
     * @param SearchRequestBuilderInterfaceFactory $searchRequestBuilderFactory
     * @param Area $area
     * @param CurrentPage $currentPage
     * @param Filter $filter
     * @param PageSize $pageSize
     * @param Sort $sort
     */
    public function __construct(
        ExtensionProvider $extensionProvider,
        SearchRequestBuilderInterfaceFactory $searchRequestBuilderFactory,
        Area $area,
        CurrentPage $currentPage,
        Filter $filter,
        PageSize $pageSize,
        Sort $sort
    ) {
        $this->extensionProvider = $extensionProvider;
        $this->searchRequestBuilderFactory = $searchRequestBuilderFactory;

        $this->resolvers = [
            self::AREA_FIELD => $area,
            self:: CURRENT_PAGE => $currentPage,
            self::PAGE_SIZE => $pageSize,
            self::SORT_FIELD => $sort,
            self::FILTERS_FIELD => $filter
        ];
    }

    /**
     * Resolve Search Request Builder parameters from arguments.
     *
     * @param string $fieldName
     * @param array $argument
     *
     * @return SearchRequestBuilderInterface
     */
    public function getFromArgument(string $fieldName, array $argument): SearchRequestBuilderInterface
    {
        $searchRequestBuilder = $this->searchRequestBuilderFactory->create();

        foreach (array_keys($argument) as $argumentName) {
            if (isset($this->resolvers[$argumentName])) {
                $this->resolvers[$argumentName]->resolve($searchRequestBuilder, $fieldName, $argumentName, $argument);
            }
        }

        $searchRequestBuilder->setSearchRequestExtension($this->extensionProvider->getExtensionAttributes($argument));

        return $searchRequestBuilder;
    }
}
