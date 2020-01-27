<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryDistanceBasedSourceSelection\Model\DistanceProvider\GoogleMap;

use Magento\Framework\Exception\LocalizedException;
use Magento\InventoryDistanceBasedSourceSelection\Model\Convert\AddressToString;
use Magento\InventoryDistanceBasedSourceSelectionApi\Api\Data\LatLngInterfaceFactory;
use Magento\InventoryDistanceBasedSourceSelectionApi\Api\GetLatsLngsFromAddressInterface;
use Magento\InventorySourceSelectionApi\Api\Data\AddressInterface;

/**
 * @inheritdoc
 */
class GetLatsLngsFromAddress implements GetLatsLngsFromAddressInterface
{
    /**
     * @var array
     */
    private $latsLngsCache = [];

    /**
     * @var LatLngInterfaceFactory
     */
    private $latLngInterfaceFactory;

    /**
     * @var AddressToString
     */
    private $addressToString;

    /**
     * @var GetGeoCodesForAddress
     */
    private $getGeoCodesForAddress;

    /**
     * @param AddressToString $addressToString
     * @param GetGeoCodesForAddress $getGeoCodesForAddress
     * @param LatLngInterfaceFactory $latLngInterfaceFactory
     */
    public function __construct(
        AddressToString $addressToString,
        GetGeoCodesForAddress $getGeoCodesForAddress,
        LatLngInterfaceFactory $latLngInterfaceFactory
    ) {
        $this->addressToString = $addressToString;
        $this->getGeoCodesForAddress = $getGeoCodesForAddress;
        $this->latLngInterfaceFactory = $latLngInterfaceFactory;
    }

    /**
     * @inheritdoc
     * @throws LocalizedException
     */
    public function execute(AddressInterface $address): array
    {
        $cacheKey = $addressString = $this->addressToString->execute($address);

        if (!isset($this->latsLngsCache[$cacheKey])) {
            $res = $this->getGeoCodesForAddress->execute($address);
            foreach ($res['results'] as $result) {
                $location = $result['geometry']['location'];
                $this->latsLngsCache[$cacheKey][] = $this->latLngInterfaceFactory->create([
                    'lat' => (float)$location['lat'],
                    'lng' => (float)$location['lng'],
                ]);

            }
        }

        return $this->latsLngsCache[$cacheKey];
    }
}
