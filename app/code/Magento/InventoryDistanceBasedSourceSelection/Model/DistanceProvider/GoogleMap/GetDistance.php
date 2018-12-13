<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryDistanceBasedSourceSelection\Model\DistanceProvider\GoogleMap;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\ClientInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\InventoryDistanceBasedSourceSelection\Model\DistanceProvider\GetDistanceInterface;
use Magento\InventoryDistanceBasedSourceSelection\Model\Request\Convert\LatLngRequestToQueryString;
use Magento\InventoryDistanceBasedSourceSelectionApi\Model\Request\LatLngRequestInterface;

/**
 * @inheritdoc
 */
class GetDistance implements GetDistanceInterface
{
    private const GOOGLE_ENDPOINT = 'https://maps.googleapis.com/maps/api/distancematrix/json';
    private const XML_PATH_MODE = 'cataloginventory/source_selection_distance_based_google/mode';
    private const XML_PATH_VALUE = 'cataloginventory/source_selection_distance_based_google/value';
    private const ZERO_RESULT_RESPONSE = "ZERO_RESULTS";

    /**
     * @var array
     */
    private $distanceCache = [];

    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var Json
     */
    private $json;

    /**
     * @var GetApiKey
     */
    private $getApiKey;

    /**
     * @var LatLngRequestToQueryString
     */
    private $latLngRequestToQueryString;

    /**
     * GetLatLngFromAddress constructor.
     *
     * @param ClientInterface $client
     * @param ScopeConfigInterface $scopeConfig
     * @param Json $json
     * @param GetApiKey $getApiKey
     * @param LatLngRequestToQueryString $latLngRequestToQueryString
     */
    public function __construct(
        ClientInterface $client,
        ScopeConfigInterface $scopeConfig,
        Json $json,
        GetApiKey $getApiKey,
        LatLngRequestToQueryString $latLngRequestToQueryString
    ) {
        $this->client = $client;
        $this->json = $json;
        $this->getApiKey = $getApiKey;
        $this->scopeConfig = $scopeConfig;
        $this->latLngRequestToQueryString = $latLngRequestToQueryString;
    }

    /**
     * @inheritdoc
     * @throws LocalizedException
     */
    public function execute(LatLngRequestInterface $source, LatLngRequestInterface $destination): float
    {
        $sourceString = $this->latLngRequestToQueryString->execute($source);
        $destinationString =  $this->latLngRequestToQueryString->execute($destination);

        $key = $sourceString . '|' . $destinationString;

        if (!isset($this->distanceCache[$key])) {


            $queryString = http_build_query([
                'key' => $this->getApiKey->execute(),
                'origins' => $sourceString,
                'destinations' => $destinationString,
                'mode' => $this->scopeConfig->getValue(self::XML_PATH_MODE),
            ]);

            $this->client->get(self::GOOGLE_ENDPOINT . '?' . $queryString);
            if ($this->client->getStatus() !== 200) {
                throw new LocalizedException(__('Unable to connect google API for distance matrix'));
            }

            $res = $this->json->unserialize($this->client->getBody());

            if ($res['status'] !== 'OK'
                || $res['rows'][0]['elements'][0]['status'] === self::ZERO_RESULT_RESPONSE
            ) {
                throw new LocalizedException(
                    __(
                        'Unable to get distance between %1 and %2',
                        $sourceString,
                        $destinationString
                    )
                );
            }

            $element = $res['rows'][0]['elements'][0];

            if ($this->scopeConfig->getValue(self::XML_PATH_VALUE) === 'time') {
                $this->distanceCache[$key] = (float)$element['duration']['value'];
            } else {
                $this->distanceCache[$key] = (float)$element['distance']['value'];
            }
        }

        return $this->distanceCache[$key];
    }
}
