<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryInStorePickupApi\Api\GetIsAnyPickupLocationAvailableInterface;
use Magento\InventoryInStorePickupApi\Api\GetPickupLocationsAssignedToSalesChannelInterface;

/**
 * @inheritdoc
 * @deprecated
 */
class GetIsAnyPickupLocationAvailable implements GetIsAnyPickupLocationAvailableInterface
{
    /**
     * @var GetPickupLocationsAssignedToSalesChannelInterface
     */
    private $getPickupLocations;

    /**
     * @param GetPickupLocationsAssignedToSalesChannelInterface $getPickupLocations
     */
    public function __construct(GetPickupLocationsAssignedToSalesChannelInterface $getPickupLocations)
    {
        $this->getPickupLocations = $getPickupLocations;
    }

    /**
     * @inheritdoc
     */
    public function execute(string $salesChannelType, string $salesChannelCode): bool
    {
        $result = false;

        try {
            $result = count($this->getPickupLocations->execute($salesChannelType, $salesChannelCode)) > 0;
        } catch (NoSuchEntityException $exception) {
            return $result;
        }

        return $result;
    }
}
