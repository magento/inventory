<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model\SearchRequest\DistanceFilter;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryDistanceBasedSourceSelectionApi\Api\GetLatLngFromAddressInterface;
use Magento\InventoryInStorePickup\Model\ResourceModel\Source\GetOrderedDistanceToSources;
use Magento\InventoryInStorePickupApi\Api\Data\SearchRequest\DistanceFilterInterface;
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
     * @param GetLatLngFromAddressInterface $getLatLngFromAddress
     * @param GetOrderedDistanceToSources $getOrderedDistanceToSources
     * @param AddressInterfaceFactory $addressInterfaceFactory
     */
    public function __construct(
        GetLatLngFromAddressInterface $getLatLngFromAddress,
        GetOrderedDistanceToSources $getOrderedDistanceToSources,
        AddressInterfaceFactory $addressInterfaceFactory
    ) {
        $this->getLatLngFromAddress = $getLatLngFromAddress;
        $this->getOrderedDistanceToSources = $getOrderedDistanceToSources;
        $this->addressInterfaceFactory = $addressInterfaceFactory;
    }

    /**
     * Get sourted by distance associated pair of Source Code and Distance to it.
     *
     * @param DistanceFilterInterface $distanceFilter
     *
     * @return float[]
     * @throws NoSuchEntityException
     */
    public function execute(DistanceFilterInterface $distanceFilter): array
    {
        $key = $this->getKey($distanceFilter);

        if (!isset($this->calculatedRequests[$key])) {
            $this->calculatedRequests[$key] = $this->getDistanceToSources($distanceFilter);
        }

        return $this->calculatedRequests[$key];
    }

    /**
     * Get key, based on filter state.
     *
     * @param DistanceFilterInterface $distanceFilter
     *
     * @return string
     */
    private function getKey(DistanceFilterInterface $distanceFilter): string
    {
        return $distanceFilter->getRadius() .
            $distanceFilter->getCountry() .
            $distanceFilter->getRegion() .
            $distanceFilter->getCity() .
            $distanceFilter->getPostcode();
    }

    /**
     * Get Distance to Sources.
     *
     * @param DistanceFilterInterface $distanceFilter
     *
     * @return float[]
     * @throws NoSuchEntityException
     */
    private function getDistanceToSources(DistanceFilterInterface $distanceFilter): array
    {
        $sourceSelectionAddress = $this->toSourceSelectionAddress($distanceFilter);
        try {
            $latLng = $this->getLatLngFromAddress->execute($sourceSelectionAddress);
        } catch (LocalizedException $exception) {
            throw new NoSuchEntityException(__($exception->getMessage()), $exception);
        }

        return $this->getOrderedDistanceToSources->execute($latLng, $distanceFilter->getRadius());
    }

    /**
     * Create Source Selection Address based on Distance Fitler from Search Request.
     *
     * @param DistanceFilterInterface $distanceFilter
     *
     * @return AddressInterface
     */
    private function toSourceSelectionAddress(DistanceFilterInterface $distanceFilter)
    {
        $data = [
            'country' => $distanceFilter->getCountry(),
            'postcode' => $distanceFilter->getPostcode() ?? '',
            'region' => $distanceFilter->getRegion() ?? '',
            'city' => $distanceFilter->getCity() ?? '',
            'street' => ''
        ];

        return $this->addressInterfaceFactory->create($data);
    }
}
