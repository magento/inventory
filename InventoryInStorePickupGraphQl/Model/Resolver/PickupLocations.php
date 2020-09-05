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
use Magento\InventoryInStorePickupApi\Api\Data\PickupLocationInterface;
use Magento\InventoryInStorePickupApi\Api\Data\SearchRequestInterface;
use Magento\InventoryInStorePickupApi\Api\Data\SearchResultInterface;
use Magento\InventoryInStorePickupApi\Api\GetPickupLocationsInterface;
use Magento\InventoryInStorePickupGraphQl\Model\Resolver\DataProvider\PickupLocation;
use Magento\InventoryInStorePickupGraphQl\Model\Resolver\PickupLocations\SearchRequestBuilder;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Resolve Pickup Locations.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class PickupLocations implements \Magento\Framework\GraphQl\Query\ResolverInterface
{
    /**
     * @var SearchRequestBuilder
     */
    private $searchRequestBuilderResolver;

    /**
     * @var GetPickupLocationsInterface
     */
    private $getPickupLocations;

    /**
     * @var PickupLocation
     */
    private $dataProvider;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param SearchRequestBuilder $searchRequestBuilderResolver
     * @param GetPickupLocationsInterface $getPickupLocations
     * @param PickupLocation $dataProvider
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        SearchRequestBuilder $searchRequestBuilderResolver,
        GetPickupLocationsInterface $getPickupLocations,
        PickupLocation $dataProvider,
        StoreManagerInterface $storeManager
    ) {
        $this->searchRequestBuilderResolver = $searchRequestBuilderResolver;
        $this->getPickupLocations = $getPickupLocations;
        $this->dataProvider = $dataProvider;
        $this->storeManager = $storeManager;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        $this->validateInput($args);

        $builder = $this->searchRequestBuilderResolver->getFromArgument($field->getName(), $args);
        $builder->setScopeCode($this->storeManager->getWebsite()->getCode());

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
        if (isset($args['currentPage']) && $args['currentPage'] < 1) {
            throw new GraphQlInputException(__('currentPage value must be greater than 0.'));
        }
        if (isset($args['pageSize']) && $args['pageSize'] < 1) {
            throw new GraphQlInputException(__('pageSize value must be greater than 0.'));
        }

        if (isset($args['distance']) && !(
            isset($args['distance']['region']) ||
            isset($args['distance']['city']) ||
            isset($args['distance']['postcode'])
        )) {
            throw new GraphQlInputException(
                __('Region or city or postcode must be specified for the filter by distance.')
            );
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

        return (int)$maxPages;
    }

    /**
     * Get Pickup Locations data.
     *
     * @param PickupLocationInterface[] $pickupLocations
     *
     * @return array
     */
    private function getPickupLocationsData(array $pickupLocations): array
    {
        $pickupLocationsData = [];

        foreach ($pickupLocations as $item) {
            $pickupLocationsData[$item->getPickupLocationCode()] = $this->dataProvider->getData($item);
        }

        return $pickupLocationsData;
    }
}
