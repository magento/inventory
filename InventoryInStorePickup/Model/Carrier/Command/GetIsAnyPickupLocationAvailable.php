<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model\Carrier\Command;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryInStorePickup\Model\GetPickupLocations;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\StockResolverInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Check if any pickup location is available in current scope.
 */
class GetIsAnyPickupLocationAvailable
{
    /**
     * @var GetPickupLocations
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
     * @param GetPickupLocations $getPickupLocations
     * @param StockResolverInterface $stockResolver
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        GetPickupLocations $getPickupLocations,
        StockResolverInterface $stockResolver,
        StoreManagerInterface $storeManager
    ) {
        $this->getPickupLocations = $getPickupLocations;
        $this->stockResolver = $stockResolver;
        $this->storeManager = $storeManager;
    }

    /**
     * @param int|null $websiteId
     *
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(?int $websiteId = null): bool
    {
        $websiteCode = $this->storeManager->getWebsite($websiteId)->getCode();

        if (!isset($this->isAvailable[$websiteCode])) {
            $this->isAvailable[$websiteCode] = $this->checkPickupLocationsAvailability($websiteCode);
        }

        return $this->isAvailable[$websiteCode];
    }

    /**
     * Check if at least one pickup location available for provided website id.
     *
     * @param string $websiteCode
     *
     * @return bool
     */
    private function checkPickupLocationsAvailability(string $websiteCode): bool
    {
        $result = false;
        try {
            $stock = $this->stockResolver->execute(
                SalesChannelInterface::TYPE_WEBSITE,
                $websiteCode
            );

            $result = count($this->getPickupLocations->execute((int)$stock->getStockId())) > 0;
        } catch (NoSuchEntityException|LocalizedException $exception) {
            return $result;
        }

        return $result;
    }
}
