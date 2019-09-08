<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupGraphQl\Model\Resolver\PickupLocations;

use Magento\Framework\Exception\LocalizedException;
use Magento\InventoryInStorePickupApi\Model\SearchRequestBuilderInterface;
use Magento\InventoryInStorePickupGraphQl\Model\Resolver\PickupLocations\SearchRequest\ResolverInterface;
use Magento\Store\Model\StoreManagerInterface;

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
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param StoreManagerInterface $storeManager
     * @param ResolverInterface[] $resolvers
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        array $resolvers
    ) {
        $this->validateResolvers($resolvers);
        $this->resolvers = $resolvers;
        $this->storeManager = $storeManager;
    }

    /**
     * @inheritdoc
     *
     * @throws LocalizedException
     */
    public function resolve(
        SearchRequestBuilderInterface $searchRequestBuilder,
        string $fieldName,
        array $argument
    ): SearchRequestBuilderInterface {
        $searchRequestBuilder->setScopeCode($this->storeManager->getWebsite()->getCode());

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
