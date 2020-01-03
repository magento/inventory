<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryDistanceBasedSourceSelection\Model\DistanceProvider\GoogleMap;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\ClientInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\InventoryDistanceBasedSourceSelection\Model\Convert\AddressToComponentsString;
use Magento\InventoryDistanceBasedSourceSelection\Model\Convert\AddressToQueryString;
use Magento\InventoryDistanceBasedSourceSelection\Model\Convert\AddressToString;
use Magento\InventoryDistanceBasedSourceSelectionApi\Api\Data\LatLngInterface;
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
    private $latLngCache = [];

    /**
     * @var LatLngInterface
     */
    private $latLngInterfaceFactory;

    /**
     * @var AddressToString
     */
    private $addressToString;

    /**
     * @var GetGeocodesForAddress
     */
    private $getGeocodesForAddress;

    /**
     * GetLatLngFromAddress constructor.
     *
     * @param ClientInterface $client
     * @param LatLngInterfaceFactory $latLngInterfaceFactory
     * @param Json $json
     * @param GetApiKey $getApiKey
     * @param AddressToComponentsString $addressToComponentsString
     * @param AddressToQueryString $addressToQueryString
     * @param AddressToString $addressToString
     * @param GetGeocodesForAddress $getGeocodesForAddress
     */
    public function __construct(
        AddressToString $addressToString,
        GetGeocodesForAddress $getGeocodesForAddress
    ) {
        $this->addressToString = $addressToString;
        $this->getGeocodesForAddress = $getGeocodesForAddress;
    }

    /**
     * @inheritdoc
     * @throws LocalizedException
     */
    public function execute(AddressInterface $address): array
    {
        $cacheKey = $addressString = $this->addressToString->execute($address);

        if (!isset($this->latLngCache[$cacheKey])) {
            $this->getGeocodesForAddress->execute($address);
            $res = $this->getGeocodesForAddress->execute($address);
            foreach ($res['results'] as $result) {
                $location = $result['geometry']['location'];
                $this->latLngCache[$cacheKey][] = $this->latLngInterfaceFactory->create([
                    'lat' => (float)$location['lat'],
                    'lng' => (float)$location['lng'],
                ]);

            }
        }

        return $this->latLngCache[$cacheKey];
    }
}
