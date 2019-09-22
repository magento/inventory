<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupGraphQl\Model\Resolver\PickupLocations\SearchRequest;

use Magento\Framework\Api\SortOrder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\InventoryInStorePickupApi\Model\SearchRequestBuilderInterface;

/**
 * Resolve Sort Order parameters.
 */
class Sort implements ResolverInterface
{
    /**
     * @var SortOrderBuilder
     */
    private $sortOrderBuilder;

    /**
     * @param SortOrderBuilder $sortOrderBuilder
     */
    public function __construct(SortOrderBuilder $sortOrderBuilder)
    {
        $this->sortOrderBuilder = $sortOrderBuilder;
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
        $sortOrders = [];
        foreach ($argument[$argumentName] as $fieldName => $fieldValue) {
            /** @var SortOrder $sortOrder */
            $sortOrders[] = $this->sortOrderBuilder
                ->setField($fieldName)
                ->setDirection(
                    $fieldValue === SortOrder::SORT_DESC ? SortOrder::SORT_DESC : SortOrder::SORT_ASC
                )->create();
        }

        return $searchRequestBuilder->setSortOrders($sortOrders);
    }
}
