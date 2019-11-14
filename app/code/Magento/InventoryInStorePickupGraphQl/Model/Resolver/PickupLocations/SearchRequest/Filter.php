<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupGraphQl\Model\Resolver\PickupLocations\SearchRequest;

use Magento\Framework\GraphQl\Query\Resolver\Argument\AstConverter;
use Magento\Framework\GraphQl\Query\Resolver\Argument\Filter\Clause;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryInStorePickupApi\Api\Data\PickupLocationInterface;
use Magento\InventoryInStorePickupApi\Model\SearchRequestBuilderInterface;

/**
 * Resolve attribute filters.
 */
class Filter implements ResolverInterface
{
    /**
     * @var AstConverter
     */
    private $astConverter;

    /**
     * @param AstConverter $astConverter
     */
    public function __construct(AstConverter $astConverter)
    {
        $this->astConverter = $astConverter;
    }

    /**
     * @inheritdoc
     */
    public function resolve(
        SearchRequestBuilderInterface $searchRequestBuilder,
        string $fieldName,
        string $argumentName,
        array $argument
    ): SearchRequestBuilderInterface {
        /** @var Clause[] $filters */
        $filters = $this->astConverter->getClausesFromAst($fieldName, $argument[$argumentName]);

        foreach ($filters as $filter) {
            $this->addFilterToBuilder($searchRequestBuilder, $filter);
        }

        return $searchRequestBuilder;
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
