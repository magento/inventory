<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryDistanceBasedSourceSelection\Model\DistanceProvider\GoogleMap;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\ClientInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\InventoryDistanceBasedSourceSelection\Model\Convert\AddressToComponentsString;
use Magento\InventoryDistanceBasedSourceSelection\Model\Convert\AddressToQueryString;
use Magento\InventoryDistanceBasedSourceSelection\Model\Convert\AddressToString;
use Magento\InventoryDistanceBasedSourceSelectionApi\Api\Data\LatLngInterface;
use Magento\InventoryDistanceBasedSourceSelectionApi\Api\Data\LatLngInterfaceFactory;
use Magento\InventoryDistanceBasedSourceSelectionApi\Api\GetLatLngFromAddressInterface;
use Magento\InventorySourceSelectionApi\Api\Data\AddressInterface;

/**
 * @inheritdoc
 */
class GetLatLngFromAddress implements GetLatLngFromAddressInterface
{
    /**
     * @var array
     */
    private $latLngCache = [];

    /**
     * @deprecated
     *
     * @var ClientInterface
     */
    private $client;

    /**
     * @var LatLngInterface
     */
    private $latLngInterfaceFactory;

    /**
     * @deprecated
     *
     * @var Json
     */
    private $json;

    /**
     * @deprecated
     *
     * @var GetApiKey
     */
    private $getApiKey;

    /**
     * @deprecated
     *
     * @var AddressToComponentsString
     */
    private $addressToComponentsString;

    /**
     * @var AddressToString
     */
    private $addressToString;

    /**
     * @deprecated
     *
     * @var AddressToQueryString
     */
    private $addressToQueryString;

    /**
     * @var GetGeoCodesForAddress
     */
    private $getGeoCodesForAddress;

    /**
     * @param ClientInterface $client
     * @param LatLngInterfaceFactory $latLngInterfaceFactory
     * @param Json $json
     * @param GetApiKey $getApiKey
     * @param AddressToComponentsString $addressToComponentsString
     * @param AddressToQueryString $addressToQueryString
     * @param AddressToString $addressToString
     * @param GetGeoCodesForAddress $getGeoCodesForAddress
     */
    public function __construct(
        ClientInterface $client,
        LatLngInterfaceFactory $latLngInterfaceFactory,
        Json $json,
        GetApiKey $getApiKey,
        AddressToComponentsString $addressToComponentsString,
        AddressToQueryString $addressToQueryString,
        AddressToString $addressToString,
        GetGeoCodesForAddress $getGeoCodesForAddress = null
    ) {
        $this->client = $client;
        $this->latLngInterfaceFactory = $latLngInterfaceFactory;
        $this->json = $json;
        $this->getApiKey = $getApiKey;
        $this->addressToComponentsString = $addressToComponentsString;
        $this->addressToString = $addressToString;
        $this->addressToQueryString = $addressToQueryString;
        $this->getGeoCodesForAddress = $getGeoCodesForAddress ?: ObjectManager::getInstance()
            ->get(GetGeoCodesForAddress::class);
    }

    /**
     * @inheritdoc
     * @throws LocalizedException
     */
    public function execute(AddressInterface $address): LatLngInterface
    {
        $cacheKey = $addressString = $this->addressToString->execute($address);

        if (!isset($this->latLngCache[$cacheKey])) {
            $res = $this->getGeoCodesForAddress->execute($address);
            $location = $res['results'][0]['geometry']['location'];
            $this->latLngCache[$cacheKey] = $this->latLngInterfaceFactory->create([
                'lat' => (float)$location['lat'],
                'lng' => (float)$location['lng'],
            ]);
        }

        return $this->latLngCache[$cacheKey];
    }
}
