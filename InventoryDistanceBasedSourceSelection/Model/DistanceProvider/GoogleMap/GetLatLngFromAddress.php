<?php
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
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
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class GetLatLngFromAddress implements GetLatLngFromAddressInterface
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
     * @var GetGeoCodesForAddress
     */
    private $getGeoCodesForAddress;

    /**
     * @param ClientInterface $client @deprecated
     * @param LatLngInterfaceFactory $latLngInterfaceFactory
     * @param Json $json @deprecated
     * @param GetApiKey $getApiKey @deprecated
     * @param AddressToComponentsString $addressToComponentsString @deprecated
     * @param AddressToQueryString $addressToQueryString @deprecated
     * @param AddressToString $addressToString
     * @param GetGeoCodesForAddress $getGeoCodesForAddress
     * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function __construct(
        ClientInterface $client,
        LatLngInterfaceFactory $latLngInterfaceFactory,
        Json $json,
        GetApiKey $getApiKey,
        AddressToComponentsString $addressToComponentsString,
        AddressToQueryString $addressToQueryString,
        AddressToString $addressToString,
        ?GetGeoCodesForAddress $getGeoCodesForAddress = null
    ) {
        $this->latLngInterfaceFactory = $latLngInterfaceFactory;
        $this->addressToString = $addressToString;
        $this->getGeoCodesForAddress = $getGeoCodesForAddress ?: ObjectManager::getInstance()
            ->get(GetGeoCodesForAddress::class);
    }

    /**
     * @inheritdoc
     *
     * @throws LocalizedException
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
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
