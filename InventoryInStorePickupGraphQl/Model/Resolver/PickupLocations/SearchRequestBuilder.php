<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupGraphQl\Model\Resolver\PickupLocations;

use Magento\Framework\Api\SortOrder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\GraphQl\Query\Resolver\Argument\AstConverter;
use Magento\Framework\GraphQl\Query\Resolver\Argument\Filter\Clause;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryInStorePickupApi\Api\Data\PickupLocationInterface;
use Magento\InventoryInStorePickupApi\Model\SearchRequestBuilderInterface;
use Magento\InventoryInStorePickupApi\Model\SearchRequestBuilderInterfaceFactory;
use Magento\InventoryInStorePickupGraphQl\Model\Resolver\PickupLocations\SearchRequestBuilder\ExtensionProvider;

/**
 * Resolve parameters for the Search Request Builder.
 */
class SearchRequestBuilder
{
    private const RADIUS_FIELD = 'radius';
    private const SEARCH_TERM = 'search_term';
    private const SORT_FIELD = 'sort';
    private const AREA_FIELD = 'area';
    private const FILTERS_FIELD = 'filters';
    private const CURRENT_PAGE = 'currentPage';
    private const PAGE_SIZE = 'pageSize';

    /**
     * @var AstConverter
     */
    private $astConverter;

    /**
     * @var SortOrderBuilder
     */
    private $sortOrderBuilder;

    /**
     * @var ExtensionProvider
     */
    private $extensionProvider;

    /**
     * @var SearchRequestBuilderInterfaceFactory
     */
    private $searchRequestBuilderFactory;

    /**
     * @param AstConverter $astConverter
     * @param SortOrderBuilder $sortOrderBuilder
     * @param ExtensionProvider $extensionProvider
     * @param SearchRequestBuilderInterfaceFactory $searchRequestBuilderFactory
     */
    public function __construct(
        AstConverter $astConverter,
        SortOrderBuilder $sortOrderBuilder,
        ExtensionProvider $extensionProvider,
        SearchRequestBuilderInterfaceFactory $searchRequestBuilderFactory
    ) {
        $this->astConverter = $astConverter;
        $this->sortOrderBuilder = $sortOrderBuilder;
        $this->extensionProvider = $extensionProvider;
        $this->searchRequestBuilderFactory = $searchRequestBuilderFactory;
    }

    /**
     * Resolve Search Request Builder parameters from arguments.
     *
     * @param string $fieldName
     * @param array $argument
     *
     * @return SearchRequestBuilderInterface
     */
    public function getBuilderFromArgument(string $fieldName, array $argument): SearchRequestBuilderInterface
    {
        $searchRequestBuilder = $this->searchRequestBuilderFactory->create();

        foreach (array_keys($argument) as $argumentName) {
            $this->resolveAttributeValue($searchRequestBuilder, $fieldName, $argumentName, $argument);
        }

        $searchRequestBuilder->setSearchRequestExtension($this->extensionProvider->getExtensionAttributes($argument));

        return $searchRequestBuilder;
    }

    /**
     * Resolve input attributes values and pass them to Search Request Builder.
     *
     * @param SearchRequestBuilderInterface $searchRequestBuilder
     * @param string $fieldName
     * @param string $argumentName
     * @param array $argument
     *
     * @return void
     */
    private function resolveAttributeValue(
        SearchRequestBuilderInterface $searchRequestBuilder,
        string $fieldName,
        string $argumentName,
        array $argument
    ): void {
        switch ($argumentName) {
            case self::AREA_FIELD:
                $searchRequestBuilder->setAreaSearchTerm($this->getAreaSearchTerm($argument));
                $searchRequestBuilder->setAreaRadius($this->getAreaRadius($argument));
                break;
            case self::FILTERS_FIELD:
                $filters = $this->getFilters($fieldName, $argument);
                foreach ($filters as $filter) {
                    $this->addFilterToBuilder($searchRequestBuilder, $filter);
                }
                break;
            case self::CURRENT_PAGE:
                $searchRequestBuilder->setCurrentPage($argument[$argumentName]);
                break;
            case self::PAGE_SIZE:
                $searchRequestBuilder->setPageSize($argument[$argumentName]);
                break;
            case self::SORT_FIELD:
                $searchRequestBuilder->setSortOrders($this->getSortOrders($argument));
                break;
        }
    }

    /**
     * Get Radius attribute value.
     *
     * @param array $argument
     *
     * @return int
     */
    private function getAreaRadius(array $argument): int
    {
        return $argument[self::AREA_FIELD][self::RADIUS_FIELD];
    }

    /**
     * Get Search Term attribute value.
     *
     * @param array $argument
     *
     * @return string
     */
    private function getAreaSearchTerm(array $argument): string
    {
        return $argument[self::AREA_FIELD][self::SEARCH_TERM];
    }

    /**
     * Get filters from arguments.
     *
     * @param string $fieldName
     * @param array $argument
     *
     * @return array
     */
    private function getFilters(string $fieldName, array $argument): array
    {
        return $this->astConverter->getClausesFromAst($fieldName, $argument[self::FILTERS_FIELD]);
    }

    /**
     * Get Sort Orders from arguments.
     *
     * @param array $argument
     *
     * @return array
     */
    private function getSortOrders(array $argument): array
    {
        $sortOrders = [];
        foreach ($argument[self::SORT_FIELD] as $fieldName => $fieldValue) {
            /** @var SortOrder $sortOrder */
            $sortOrders[] = $this->sortOrderBuilder
                ->setField($fieldName)
                ->setDirection(
                    $fieldValue === SortOrder::SORT_DESC ? SortOrder::SORT_DESC : SortOrder::SORT_ASC
                )->create();
        }

        return $sortOrders;
    }

    /**
     * Add filter to the Search Request Builder.
     *
     * @param SearchRequestBuilderInterface $searchRequestBuilder
     * @param Clause $filter
     */
    private function addFilterToBuilder(SearchRequestBuilderInterface $searchRequestBuilder, Clause $filter): void
    {
        switch ($filter->getFieldName()) {
            case SourceInterface::COUNTRY_ID:
                $searchRequestBuilder->setCountryFilter(
                    $this->getClauseValue($filter),
                    $filter->getClauseType()
                );
                break;
            case SourceInterface::POSTCODE:
                $searchRequestBuilder->setPostcodeFilter(
                    $this->getClauseValue($filter),
                    $filter->getClauseType()
                );
                break;
            case SourceInterface::REGION:
                $searchRequestBuilder->setRegionFilter(
                    $this->getClauseValue($filter),
                    $filter->getClauseType()
                );
                break;
            case SourceInterface::REGION_ID:
                $searchRequestBuilder->setRegionIdFilter(
                    $this->getClauseValue($filter),
                    $filter->getClauseType()
                );
                break;
            case SourceInterface::CITY:
                $searchRequestBuilder->setCityFilter($this->getClauseValue($filter), $filter->getClauseType());
                break;
            case SourceInterface::STREET:
                $searchRequestBuilder->setStreetFilter(
                    $this->getClauseValue($filter),
                    $filter->getClauseType()
                );
                break;
            case PickupLocationInterface::PICKUP_LOCATION_CODE:
                $searchRequestBuilder->setPickupLocationCodeFilter(
                    $this->getClauseValue($filter),
                    $filter->getClauseType()
                );
                break;
            case SourceInterface::NAME:
                $searchRequestBuilder->setNameFilter($this->getClauseValue($filter), $filter->getClauseType());
                break;
        }
    }

    /**
     * Get value from the clause.
     *
     * @param Clause $filter
     *
     * @return string
     */
    private function getClauseValue(Clause $filter): string
    {
        $value = $filter->getClauseValue();

        return is_array($value) ? implode(',', $value) : $value;
    }
}
