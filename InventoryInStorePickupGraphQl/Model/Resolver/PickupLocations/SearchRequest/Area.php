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
class Area implements ResolverInterface
{
    private const RADIUS_FIELD = 'radius';
    private const SEARCH_TERM = 'search_term';

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
        $searchRequestBuilder->setAreaSearchTerm($filterData[self::SEARCH_TERM]);

        return $searchRequestBuilder;
    }
}
