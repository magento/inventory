<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupGraphQl\Model\Resolver\PickupLocations\SearchRequest;

use Magento\InventoryInStorePickupApi\Model\SearchRequestBuilderInterface;

/**
 * Resolve Distance Filter parameters.
 */
class Distance implements ResolverInterface
{
    private const RADIUS_FIELD = 'radius';
    private const COUNTRY_FIELD = 'country_code';
    private const REGION_FIELD = 'region';
    private const CITY_FIELD = 'city';
    private const POSTCODE_FIELD = 'postcode';

    /**
     * @inheritdoc
     */
    public function resolve(
        SearchRequestBuilderInterface $searchRequestBuilder,
        string $fieldName,
        string $argumentName,
        array $argument
    ): SearchRequestBuilderInterface {
        $filterData = $argument[$argumentName];

        $searchRequestBuilder->setAreaRadius($filterData[self::RADIUS_FIELD]);
        $searchRequestBuilder->setDistanceFilterCountry($filterData[self::COUNTRY_FIELD]);

        if (isset($filterData[self::POSTCODE_FIELD])) {
            $searchRequestBuilder->setDistanceFilterPostcode($filterData[self::POSTCODE_FIELD]);
        }

        if (isset($filterData[self::CITY_FIELD])) {
            $searchRequestBuilder->setDistanceFilterCity($filterData[self::CITY_FIELD]);
        }

        if (isset($filterData[self::REGION_FIELD])) {
            $searchRequestBuilder->setDistanceFilterRegion($filterData[self::REGION_FIELD]);
        }

        return $searchRequestBuilder;
    }
}
