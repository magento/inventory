<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model\Carrier\Command;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryInStorePickupApi\Api\GetPickupLocationsAssignedToStockOrderedByPriorityInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\StockResolverInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Check if any pickup location is available in current scope.
 */
class GetIsAnyPickupLocationAvailable
{
    /**
     * @var GetPickupLocationsAssignedToStockOrderedByPriorityInterface
     */
    private $getPickupLocations;

    /**
     * @var StockResolverInterface
     */
    private $stockResolver;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * Runtime cached values.
     *
     * @var array
     */
    private $isAvailable = [];

    /**
     * @param GetPickupLocationsAssignedToStockOrderedByPriorityInterface $getPickupLocations
     * @param StockResolverInterface $stockResolver
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        GetPickupLocationsAssignedToStockOrderedByPriorityInterface $getPickupLocations,
        StockResolverInterface $stockResolver,
        StoreManagerInterface $storeManager
    ) {
        $this->getPickupLocations = $getPickupLocations;
        $this->stockResolver = $stockResolver;
        $this->storeManager = $storeManager;
    }

    /**
     * @param int $websiteId
     *
     * @return bool
     */
    public function execute(int $websiteId): bool
    {
        if (!isset($this->isAvailable[$websiteId])) {
            $this->isAvailable[$websiteId] = $this->checkPickupLocationsAvailability($websiteId);
        }

        return $this->isAvailable[$websiteId];
    }

    /**
     * Check if at least one pickup location available for provided website id.
     *
     * @param int $websiteId
     *
     * @return bool
     */
    private function checkPickupLocationsAvailability(int $websiteId): bool
    {
        $result = false;
        try {
            $stock = $this->stockResolver->execute(
                SalesChannelInterface::TYPE_WEBSITE,
                $this->storeManager->getWebsite($websiteId)->getCode()
            );

            $result = count($this->getPickupLocations->execute((int)$stock->getStockId())) > 0;
        } catch (NoSuchEntityException|LocalizedException $exception) {
            return $result;
        }

        return $result;
    }
}
