<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model\SearchRequest\Area;

use Magento\Framework\Exception\LocalizedException;
use Magento\InventoryDistanceBasedSourceSelectionApi\Api\GetLatsLngsFromAddressInterface;
use Magento\InventoryInStorePickup\Model\ResourceModel\Source\GetOrderedDistanceToSources;
use Magento\InventoryInStorePickupApi\Api\Data\SearchRequest\AreaInterface;
use Magento\InventoryInStorePickupApi\Model\SearchRequest\Area\Pipeline;
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
     * @var GetLatsLngsFromAddressInterface
     */
    private $getLatsLngsFromAddress;

    /**
     * @var GetOrderedDistanceToSources
     */
    private $getOrderedDistanceToSources;

    /**
     * @var AddressInterfaceFactory
     */
    private $addressInterfaceFactory;

    /**
     * @var Pipeline
     */
    private $searchTermPipeline;

    /**
     * @param GetLatsLngsFromAddressInterface $getLatsLngsFromAddress
     * @param GetOrderedDistanceToSources $getOrderedDistanceToSources
     * @param AddressInterfaceFactory $addressInterfaceFactory
     * @param Pipeline $searchTermPipeline
     */
    public function __construct(
        GetLatsLngsFromAddressInterface $getLatsLngsFromAddress,
        GetOrderedDistanceToSources $getOrderedDistanceToSources,
        AddressInterfaceFactory $addressInterfaceFactory,
        Pipeline $searchTermPipeline
    ) {
        $this->getLatsLngsFromAddress = $getLatsLngsFromAddress;
        $this->getOrderedDistanceToSources = $getOrderedDistanceToSources;
        $this->addressInterfaceFactory = $addressInterfaceFactory;
        $this->searchTermPipeline = $searchTermPipeline;
    }

    /**
     * Get sourted by distance associated pair of Source Code and Distance to it.
     *
     * @param AreaInterface $area
     *
     * @return float[]
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
     */
    private function getDistanceToSources(AreaInterface $area): array
    {
        $sourceSelectionAddress = $this->toSourceSelectionAddress($area);
        try {
            $latsLngs = $this->getLatsLngsFromAddress->execute($sourceSelectionAddress);
        } catch (LocalizedException $exception) {
            return [];
        }

        return $this->getOrderedDistanceToSources->execute($latsLngs, $area->getRadius());
    }

    /**
     * Create Source Selection Address based on Distance Fitler from Search Request.
     *
     * @param AreaInterface $area
     *
     * @return AddressInterface
     */
    private function toSourceSelectionAddress(AreaInterface $area): AddressInterface
    {
        $data = [
            'postcode' => '',
            'region' => '',
            'city' => '',
            'street' => ''
        ];

        $searchTermData = $this->searchTermPipeline->execute($area->getSearchTerm());
        return $this->addressInterfaceFactory->create(array_merge($data, $searchTermData->getData()));
    }
}
