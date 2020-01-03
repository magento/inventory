<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryDistanceBasedSourceSelectionApi\Model;

use Magento\InventoryDistanceBasedSourceSelectionApi\Api\GetDistanceProviderCodeInterface;
use Magento\InventoryDistanceBasedSourceSelectionApi\Api\GetLatsLngsFromAddressInterface;
use Magento\InventoryDistanceBasedSourceSelectionApi\Exception\NoSuchLatsLngsFromAddressProviderException;
use Magento\InventorySourceSelectionApi\Api\Data\AddressInterface;

/**
 * Get latitude and longitude objects from address.
 *
 * @api
 */
class GetLatsLngsFromAddress implements GetLatsLngsFromAddressInterface
{
    /**
     * @var GetLatsLngsFromAddressInterface[]
     */
    private $providers;

    /**
     * @var GetDistanceProviderCodeInterface
     */
    private $getDistanceProviderCode;

    /**
     * GetLatLngFromSource constructor.
     *
     * @param GetDistanceProviderCodeInterface $getDistanceProviderCode
     * @param GetLatsLngsFromAddressInterface[] $providers
     * @SuppressWarnings(PHPMD.LongVariable)
     */
    public function __construct(
        GetDistanceProviderCodeInterface $getDistanceProviderCode,
        array $providers
    ) {
        foreach ($providers as $providerCode => $provider) {
            if (!($provider instanceof GetLatsLngsFromAddressInterface)) {
                throw new \InvalidArgumentException(
                    'LatsLngs provider ' . $providerCode . ' must implement ' . GetLatsLngsFromAddressInterface::class
                );
            }
        }

        $this->providers = $providers;
        $this->getDistanceProviderCode = $getDistanceProviderCode;
    }

    /**
     * @inheritdoc
     * @throws NoSuchLatsLngsFromAddressProviderException
     */
    public function execute(AddressInterface $address): array
    {
        $code = $this->getDistanceProviderCode->execute();
        if (!isset($this->providers[$code])) {
            throw new NoSuchLatsLngsFromAddressProviderException(
                __('No such latitude and longitude from address provider: %1', $code)
            );
        }

        return $this->providers[$code]->execute($address);
    }
}
