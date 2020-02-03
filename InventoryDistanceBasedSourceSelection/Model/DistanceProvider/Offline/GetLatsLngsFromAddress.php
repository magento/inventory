<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryDistanceBasedSourceSelection\Model\DistanceProvider\Offline;

use Magento\Framework\Exception\LocalizedException;
use Magento\InventoryDistanceBasedSourceSelection\Model\Convert\AddressToString;
use Magento\InventoryDistanceBasedSourceSelection\Model\ResourceModel\GetGeoNamesDataByAddress;
use Magento\InventoryDistanceBasedSourceSelectionApi\Api\Data\LatLngInterfaceFactory;
use Magento\InventoryDistanceBasedSourceSelectionApi\Api\GetLatsLngsFromAddressInterface;
use Magento\InventorySourceSelectionApi\Api\Data\AddressInterface;

/**
 * @inheritdoc
 */
class GetLatsLngsFromAddress implements GetLatsLngsFromAddressInterface
{
    /**
     * Longitudes and latitudes local cache for address.
     *
     * @var array
     */
    private $latsLngsCache = [];

    /**
     * @var LatLngInterfaceFactory
     */
    private $latLngInterfaceFactory;

    /**
     * @var GetGeoNamesDataByAddress
     */
    private $getGeoNamesDataByAddress;

    /**
     * @var AddressToString
     */
    private $addressToString;

    /**
     * @param GetGeoNamesDataByAddress $getGeoNamesDataByAddress
     * @param LatLngInterfaceFactory $latLngInterfaceFactory
     * @param AddressToString $addressToString
     */
    public function __construct(
        GetGeoNamesDataByAddress $getGeoNamesDataByAddress,
        LatLngInterfaceFactory $latLngInterfaceFactory,
        AddressToString $addressToString
    ) {
        $this->getGeoNamesDataByAddress = $getGeoNamesDataByAddress;
        $this->latLngInterfaceFactory = $latLngInterfaceFactory;
        $this->addressToString = $addressToString;
    }

    /**
     * @inheritdoc
     * @throws LocalizedException
     */
    public function execute(AddressInterface $address): array
    {
        $cacheKey = $this->addressToString->execute($address);
        if (!isset($this->latsLngsCache[$cacheKey])) {
            $geoNamesData = $this->getGeoNamesDataByAddress->execute($address);
            foreach ($geoNamesData as $geoNameData) {
                $this->latsLngsCache[$cacheKey][] = $this->latLngInterfaceFactory->create([
                    'lat' => (float)$geoNameData['latitude'],
                    'lng' => (float)$geoNameData['longitude'],
                ]);
            }
        }

        return $this->latsLngsCache[$cacheKey];
    }
}
