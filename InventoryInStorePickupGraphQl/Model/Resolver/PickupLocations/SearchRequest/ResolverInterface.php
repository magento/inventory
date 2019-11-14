<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupGraphQl\Model\Resolver\PickupLocations\SearchRequest;

use Magento\InventoryInStorePickupApi\Model\SearchRequestBuilderInterface;

/**
 * Resolve arguments for Search Request Builder.
 */
interface ResolverInterface
{
    /**
     * Resolve arguments for Search Request Builder.
     *
     * @param SearchRequestBuilderInterface $searchRequestBuilder
     * @param string $fieldName
     * @param string $argumentName
     * @param array $argument
     *
     * @return SearchRequestBuilderInterface
     */
    public function resolve(
        SearchRequestBuilderInterface $searchRequestBuilder,
        string $fieldName,
        string $argumentName,
        array $argument
    ): SearchRequestBuilderInterface;
}
