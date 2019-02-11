<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupApi\Model;

use Magento\InventoryDistanceBasedSourceSelectionApi\Api\GetDistanceProviderCodeInterface;
use Magento\InventoryInStorePickupApi\Api\GetNearbySourcesFromPostcodeInterface;

/**
 * Get latitude and longitude object from address
 *
 * @api
 */
class GetNearbySourcesFromPostcode implements GetNearbySourcesFromPostcodeInterface
{
    /**
     * @var GetNearbySourcesFromPostcodeInterface[]
     */
    private $providers;

    /**
     * @var GetDistanceProviderCodeInterface
     */
    private $getDistanceProviderCode;

    /**
     * GetNearbySourcesFromPostcode constructor.
     *
     * @param GetDistanceProviderCodeInterface $getDistanceProviderCode
     * @param GetNearbySourcesFromPostcodeInterface[] $providers
     * @SuppressWarnings(PHPMD.LongVariable)
     */
    public function __construct(
        GetDistanceProviderCodeInterface $getDistanceProviderCode,
        array $providers
    ) {
        foreach ($providers as $providerCode => $provider) {
            if (!($provider instanceof GetNearbySourcesFromPostcodeInterface)) {
                throw new \InvalidArgumentException(
                    'LatLng provider ' . $providerCode . ' must implement ' . GetNearbySourcesFromPostcodeInterface::class
                );
            }
        }

        $this->providers = $providers;
        $this->getDistanceProviderCode = $getDistanceProviderCode;
    }

    /**
     * @inheritdoc
     */
    public function execute(string $country, string $zipcode, int $radius)
    {
        $code = $this->getDistanceProviderCode->execute();
        if (!isset($this->providers[$code])) {
            throw new NoSuchEntityException(
                __('No such sources from postcode provider: %1', $code)
            );
        }

        return $this->providers[$code]->execute($country, $zipcode, $radius);
    }

}