<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupApi\Model;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryDistanceBasedSourceSelectionApi\Api\GetDistanceProviderCodeInterface;
use Magento\InventoryInStorePickupApi\Api\GetNearbySourcesByPostcodeInterface;

/**
 * Get nearby sources of a given zip code.
 *
 * @api
 */
class GetNearbySourcesByPostcode implements GetNearbySourcesByPostcodeInterface
{
    /**
     * @var GetNearbySourcesByPostcodeInterface[]
     */
    private $providers;

    /**
     * @var GetDistanceProviderCodeInterface
     */
    private $getDistanceProviderCode;

    /**
     * @param GetDistanceProviderCodeInterface $getDistanceProviderCode
     * @param GetNearbySourcesByPostcodeInterface[] $providers
     */
    public function __construct(
        GetDistanceProviderCodeInterface $getDistanceProviderCode,
        array $providers
    ) {
        foreach ($providers as $providerCode => $provider) {
            if (!($provider instanceof GetNearbySourcesByPostcodeInterface)) {
                throw new \InvalidArgumentException(
                    sprintf(
                        "Nearby Sources provider %s must implement %s",
                        $providerCode,
                        GetNearbySourcesByPostcodeInterface::class
                    )
                );
            }
        }

        $this->providers = $providers;
        $this->getDistanceProviderCode = $getDistanceProviderCode;
    }

    /**
     * @inheritdoc
     */
    public function execute(string $country, string $postcode, int $radius): array
    {
        $code = $this->getDistanceProviderCode->execute();
        if (!isset($this->providers[$code])) {
            throw new NoSuchEntityException(
                __('No such sources from postcode provider: %1', $code)
            );
        }

        return $this->providers[$code]->execute($country, $postcode, $radius);
    }
}
