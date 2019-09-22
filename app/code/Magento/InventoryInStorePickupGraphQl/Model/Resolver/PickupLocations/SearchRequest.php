<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupGraphQl\Model\Resolver\PickupLocations;

use Magento\InventoryInStorePickupApi\Model\SearchRequestBuilderInterface;
use Magento\InventoryInStorePickupGraphQl\Model\Resolver\PickupLocations\SearchRequest\ResolverInterface;

/**
 * Resolve parameters for the Search Request Builder.
 */
class SearchRequest
{
    /**
     * @var ResolverInterface[]
     */
    private $resolvers;

    /**
     * @param ResolverInterface[] $resolvers
     */
    public function __construct(
        array $resolvers
    ) {
        $this->validateResolvers($resolvers);
        $this->resolvers = $resolvers;
    }

    /**
     * @inheritdoc
     */
    public function resolve(
        SearchRequestBuilderInterface $searchRequestBuilder,
        string $fieldName,
        array $argument
    ): SearchRequestBuilderInterface {
        foreach ($this->resolvers as $argumentName => $resolver) {
            if (isset($argument[$argumentName])) {
                $searchRequestBuilder = $resolver->resolve(
                    $searchRequestBuilder,
                    $fieldName,
                    $argumentName,
                    $argument
                );
            }
        }

        return $searchRequestBuilder;
    }

    /**
     * Validate input types.
     *
     * @param ResolverInterface[] $resolvers
     *
     * @return void
     * @throws \InvalidArgumentException
     */
    private function validateResolvers(array $resolvers): void
    {
        foreach ($resolvers as $resolver) {
            if (!$resolver instanceof ResolverInterface) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'Pickup Location Search Request Resolver must implement %s.' .
                        '%s has been received instead.',
                        ResolverInterface::class,
                        get_class($resolver)
                    )
                );
            }
        }
    }
}
