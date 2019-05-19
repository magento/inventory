<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryInStorePickupApi\Api\GetIsAnyPickupLocationAvailableInterface;
use Magento\InventoryInStorePickupApi\Api\GetPickupLocationsAssignedToStockOrderedByPriorityInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\GetStockBySalesChannelInterface;

/**
 * @inheritdoc
 */
class GetIsAnyPickupLocationAvailable implements GetIsAnyPickupLocationAvailableInterface
{
    /**
     * @var GetPickupLocationsAssignedToStockOrderedByPriorityInterface
     */
    private $getPickupLocations;

    /**
     * @var GetStockBySalesChannelInterface
     */
    private $getStockBySalesChannel;

    /**
     * @param GetPickupLocationsAssignedToStockOrderedByPriorityInterface $getPickupLocations
     * @param GetStockBySalesChannelInterface $getStockBySalesChannel
     */
    public function __construct(
        GetPickupLocationsAssignedToStockOrderedByPriorityInterface $getPickupLocations,
        GetStockBySalesChannelInterface $getStockBySalesChannel
    ) {
        $this->getPickupLocations = $getPickupLocations;
        $this->getStockBySalesChannel = $getStockBySalesChannel;
    }

    /**
     * @inheritdoc
     */
    public function execute(SalesChannelInterface $salesChannel): bool
    {
        return $this->checkPickupLocationsAvailability($salesChannel);
    }

    /**
     * Check if at least one pickup location available for provided Sales Channel.
     *
     * @param SalesChannelInterface $salesChannel
     *
     * @return bool
     */
    private function checkPickupLocationsAvailability(SalesChannelInterface $salesChannel): bool
    {
        $result = false;
        try {
            $stock = $this->getStockBySalesChannel->execute($salesChannel);

            $result = count($this->getPickupLocations->execute((int)$stock->getStockId())) > 0;
        } catch (NoSuchEntityException $exception) {
            return $result;
        }

        return $result;
    }
}
