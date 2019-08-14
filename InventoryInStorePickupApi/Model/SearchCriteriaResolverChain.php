<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupApi\Model;

use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\InventoryInStorePickupApi\Api\Data\SearchRequestInterface;

/**
 * @inheritdoc
 */
class SearchCriteriaResolverChain implements SearchCriteriaResolverInterface
{
    /**
     * @var SearchCriteriaBuilderFactory
     */
    private $searchCriteriaBuilderFactory;

    /**
     * @var BuilderPartsResolverInterface[]
     */
    private $resolvers;

    /**
     * @param SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory
     * @param BuilderPartsResolverInterface[] $resolvers
     */
    public function __construct(
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        array $resolvers
    ) {
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
        $this->resolvers = $resolvers;

        $this->validateResolvers($resolvers);
    }

    /**
     * @inheritdoc
     */
    public function resolve(SearchRequestInterface $searchRequest): SearchCriteria
    {
        $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();

        foreach ($this->resolvers as $resolver) {
            $resolver->resolve($searchRequest, $searchCriteriaBuilder);
        }

        return $searchCriteriaBuilder->create();
    }

    /**
     * Validate input array.
     *
     * @param BuilderPartsResolverInterface[] $resolvers
     */
    private function validateResolvers(array $resolvers): void
    {
        foreach ($resolvers as $resolver) {
            if (!$resolver instanceof BuilderPartsResolverInterface) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'Pickup Locations Search Criteria Resolver must implement %s.' .
                        '%s has been received instead.',
                        BuilderPartsResolverInterface::class,
                        get_class($resolver)
                    )
                );
            }
        }
    }
}
