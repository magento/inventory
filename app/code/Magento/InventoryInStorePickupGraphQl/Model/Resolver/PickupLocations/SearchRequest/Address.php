<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupGraphQl\Model\Resolver\PickupLocations\SearchRequest;

use Magento\Framework\GraphQl\Query\Resolver\Argument\AstConverter;
use Magento\Framework\GraphQl\Query\Resolver\Argument\Filter\Clause;
use Magento\InventoryInStorePickupApi\Model\SearchRequestBuilderInterface;

/**
 * Resolve address filters parameters.
 */
class Address implements ResolverInterface
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
            $this->applyFilterToBuilder($searchRequestBuilder, $filter);
        }

        return $searchRequestBuilder;
    }

    private function applyFilterToBuilder(SearchRequestBuilderInterface $searchRequestBuilder, Clause $filter): void
    {
        switch ($filter->getFieldName()) {
            case 'country_id':
                $searchRequestBuilder->setAddressCountryFilter($filter->getClauseValue(), $filter->getClauseType());
                break;
            case 'postcode':
                $searchRequestBuilder->setAddressPostcodeFilter($filter->getClauseValue(), $filter->getClauseType());
                break;
            case 'region':
                $searchRequestBuilder->setAddressRegionFilter($filter->getClauseValue(), $filter->getClauseType());
                break;
            case 'region_id':
                $searchRequestBuilder->setAddressRegionIdFilter($filter->getClauseValue(), $filter->getClauseType());
                break;
            case 'city':
                $searchRequestBuilder->setAddressCityFilter($filter->getClauseValue(), $filter->getClauseType());
                break;
            case 'street':
                $searchRequestBuilder->setAddressStreetFilter($filter->getClauseValue(), $filter->getClauseType());
                break;
        }
    }
}
