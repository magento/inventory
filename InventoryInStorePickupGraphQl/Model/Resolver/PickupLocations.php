<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupGraphQl\Model\Resolver;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\InventoryInStorePickup\Model\SearchRequestBuilder;
use Magento\InventoryInStorePickupApi\Api\Data\PickupLocationInterface;
use Magento\InventoryInStorePickupApi\Api\Data\SearchRequestInterface;
use Magento\InventoryInStorePickupApi\Api\Data\SearchResultInterface;
use Magento\InventoryInStorePickupApi\Api\GetPickupLocationsInterface;
use Magento\InventoryInStorePickupGraphQl\Model\Resolver\DataProvider\PickupLocation;
use Magento\InventoryInStorePickupGraphQl\Model\Resolver\PickupLocations\SearchRequest;

/**
 * Resolve Pickup Locations.
 */
class PickupLocations implements \Magento\Framework\GraphQl\Query\ResolverInterface
{
    /**
     * @var SearchRequest
     */
    private $searchRequestResolver;

    /**
     * @var SearchRequestBuilder
     */
    private $searchRequestBuilder;

    /**
     * @var GetPickupLocationsInterface
     */
    private $getPickupLocations;

    /**
     * @var PickupLocation
     */
    private $dataProvider;

    /**
     * @param SearchRequest $searchRequestResolver
     * @param SearchRequestBuilder $searchRequestBuilder
     * @param GetPickupLocationsInterface $getPickupLocations
     * @param PickupLocation $dataProvider
     */
    public function __construct(
        SearchRequest $searchRequestResolver,
        SearchRequestBuilder $searchRequestBuilder,
        GetPickupLocationsInterface $getPickupLocations,
        PickupLocation $dataProvider
    ) {
        $this->searchRequestResolver = $searchRequestResolver;
        $this->searchRequestBuilder = $searchRequestBuilder;
        $this->getPickupLocations = $getPickupLocations;
        $this->dataProvider = $dataProvider;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        $this->validateInput($args);

        $builder = $this->searchRequestResolver->resolve($this->searchRequestBuilder, $field->getName(), $args);

        $searchRequest = $builder->create();
        try {
            $searchResult = $this->getPickupLocations->execute($searchRequest);
        } catch (NoSuchEntityException $e) {
            throw new GraphQlNoSuchEntityException(__($e->getMessage()), $e);
        }

        return [
            'items' => $this->getPickupLocationsData($searchResult->getItems()),
            'page_info' => [
                'page_size' => $searchRequest->getPageSize(),
                'current_page' => $searchRequest->getCurrentPage(),
                'total_pages' => $this->getMaxPage($searchRequest, $searchResult)

            ],
            'total_count' => $searchResult->getTotalCount()
        ];
    }

    /**
     * Validate input.
     *
     * @param array $args
     *
     * @throws GraphQlInputException
     */
    private function validateInput(array $args): void
    {
        if (isset($args['search_request']['currentPage']) && $args['search_request']['currentPage'] < 1) {
            throw new GraphQlInputException(__('currentPage value must be greater than 0.'));
        }
        if (isset($args['search_request']['pageSize']) && $args['search_request']['pageSize'] < 1) {
            throw new GraphQlInputException(__('pageSize value must be greater than 0.'));
        }

        if (isset($args['search_request']['distance_filter']) && !(
            $args['search_request']['distance_filter']['region'] ||
            $args['search_request']['distance_filter']['city'] ||
            $args['search_request']['distance_filter']['postcode']
        )) {
            throw new GraphQlInputException(__('Region or city or postcode must be specified for distance filter.'));
        }
    }

    /**
     * Get maximum number of pages.
     *
     * @param SearchRequestInterface $searchRequest
     * @param SearchResultInterface $searchResult
     *
     * @return int
     * @throws GraphQlInputException
     */
    private function getMaxPage(SearchRequestInterface $searchRequest, SearchResultInterface $searchResult): int
    {
        if ($searchRequest->getPageSize()) {
            $maxPages = ceil($searchResult->getTotalCount() / $searchRequest->getPageSize());
        } else {
            $maxPages = 0;
        }

        $currentPage = $searchRequest->getCurrentPage();
        if ($searchRequest->getCurrentPage() > $maxPages && $searchResult->getTotalCount() > 0) {
            throw new GraphQlInputException(
                __(
                    'currentPage value %1 specified is greater than the %2 page(s) available.',
                    [$currentPage, $maxPages]
                )
            );
        }

        return (int) $maxPages;
    }

    /**
     * Get Pickup Locations data.
     *
     * @param $pickupLocations
     *
     * @return array
     */
    private function getPickupLocationsData($pickupLocations)
    {
        $pickupLocationsData = [];
        /** @var PickupLocationInterface $item */
        foreach ($pickupLocations as $item) {
            $pickupLocationsData[$item->getPickupLocationCode()] = $this->dataProvider->getData($item);
        }

        return $pickupLocationsData;
    }
}
