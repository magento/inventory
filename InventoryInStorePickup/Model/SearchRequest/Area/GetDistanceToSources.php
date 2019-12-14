<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model\SearchRequest\Area;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryDistanceBasedSourceSelectionApi\Api\GetLatLngFromAddressInterface;
use Magento\InventoryInStorePickup\Model\ResourceModel\Source\GetOrderedDistanceToSources;
use Magento\InventoryInStorePickupApi\Api\Data\SearchRequest\AreaInterface;
use Magento\InventoryInStorePickupApi\Model\SearchResult\Area\HandleSearchTerm;
use Magento\InventorySourceSelectionApi\Api\Data\AddressInterface;
use Magento\InventorySourceSelectionApi\Api\Data\AddressInterfaceFactory;

/**
 * Provide associated list of Source codes and distance to them in KM.
 */
class GetDistanceToSources
{
    /**
     * Cached list of already calculated distances to Sources.
     *
     * @var float
     */
    private $calculatedRequests = [];

    /**
     * @var GetLatLngFromAddressInterface
     */
    private $getLatLngFromAddress;

    /**
     * @var GetOrderedDistanceToSources
     */
    private $getOrderedDistanceToSources;

    /**
     * @var AddressInterfaceFactory
     */
    private $addressInterfaceFactory;

    /**
     * @var HandleSearchTerm
     */
    private $handleSearchTerm;

    /**
     * @param GetLatLngFromAddressInterface $getLatLngFromAddress
     * @param GetOrderedDistanceToSources $getOrderedDistanceToSources
     * @param AddressInterfaceFactory $addressInterfaceFactory
     * @param HandleSearchTerm $handleSearchTerm
     */
    public function __construct(
        GetLatLngFromAddressInterface $getLatLngFromAddress,
        GetOrderedDistanceToSources $getOrderedDistanceToSources,
        AddressInterfaceFactory $addressInterfaceFactory,
        HandleSearchTerm $handleSearchTerm
    ) {
        $this->getLatLngFromAddress = $getLatLngFromAddress;
        $this->getOrderedDistanceToSources = $getOrderedDistanceToSources;
        $this->addressInterfaceFactory = $addressInterfaceFactory;
        $this->handleSearchTerm = $handleSearchTerm;
    }

    /**
     * Get sourted by distance associated pair of Source Code and Distance to it.
     *
     * @param AreaInterface $area
     *
     * @return float[]
     * @throws NoSuchEntityException
     */
    public function execute(AreaInterface $area): array
    {
        $key = $this->getKey($area);

        if (!isset($this->calculatedRequests[$key])) {
            $this->calculatedRequests[$key] = $this->getDistanceToSources($area);
        }

        return $this->calculatedRequests[$key];
    }

    /**
     * Get key, based on filter state.
     *
     * @param AreaInterface $area
     *
     * @return string
     */
    private function getKey(AreaInterface $area): string
    {
        return $area->getRadius() . $area->getSearchTerm();
    }

    /**
     * Get Distance to Sources.
     *
     * @param AreaInterface $area
     *
     * @return float[]
     * @throws NoSuchEntityException
     */
    private function getDistanceToSources(AreaInterface $area): array
    {
        $sourceSelectionAddress = $this->toSourceSelectionAddress($area);
        try {
            $latLng = $this->getLatLngFromAddress->execute($sourceSelectionAddress);
        } catch (LocalizedException $exception) {
            throw new NoSuchEntityException(__($exception->getMessage()), $exception);
        }

        return $this->getOrderedDistanceToSources->execute($latLng, $area->getRadius());
    }

    /**
     * Create Source Selection Address based on Distance Fitler from Search Request.
     *
     * @param AreaInterface $area
     *
     * @return AddressInterface
     */
    private function toSourceSelectionAddress(AreaInterface $area)
    {
        $data = [
            'region' => '',
            'street' => ''
        ];

        $searchTermData = $this->handleSearchTerm->execute($area->getSearchTerm());
        return $this->addressInterfaceFactory->create(array_merge($data, $searchTermData->getData()));
    }
}
