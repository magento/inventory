<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryInStorePickupApi\Api\Data\PickupLocationInterface;
use Magento\InventoryInStorePickupApi\Api\GetPickupLocationsInterface;
use Magento\InventoryInStorePickupApi\Model\GetPickupLocationInterface;
use Magento\InventoryInStorePickupApi\Model\SearchRequestBuilderInterface;

/**
 * @inheritdoc
 */
class GetPickupLocation implements GetPickupLocationInterface
{
    /**
     * @var GetPickupLocationsInterface
     */
    private $getPickupLocations;

    /**
     * @var SearchRequestBuilderInterface
     */
    private $searchRequestBuilder;

    /**
     * @param GetPickupLocationsInterface $getPickupLocations
     * @param SearchRequestBuilderInterface $searchRequestBuilder
     */
    public function __construct(
        GetPickupLocationsInterface $getPickupLocations,
        SearchRequestBuilderInterface $searchRequestBuilder
    ) {
        $this->getPickupLocations = $getPickupLocations;
        $this->searchRequestBuilder = $searchRequestBuilder;
    }

    /**
     * @inheritdoc
     */
    public function execute(
        string $pickupLocationCode,
        string $salesChannelType,
        string $salesChannelCode
    ): PickupLocationInterface {
        $searchRequest = $this->searchRequestBuilder->setPickupLocationCodeFilter($pickupLocationCode)
            ->setScopeCode($salesChannelCode)
            ->setScopeType($salesChannelType)
            ->setPageSize(1)
            ->create();

        $searchResult = $this->getPickupLocations->execute($searchRequest);

        if ($searchResult->getTotalCount() === 0) {
            throw new NoSuchEntityException(
                __(
                    'Can not find Pickup Location with code %1 for %2 Sales Channel "%3".',
                    [$pickupLocationCode, $salesChannelType, $salesChannelCode]
                )
            );
        }

        return current($searchResult->getItems());
    }
}
