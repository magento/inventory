<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryDistanceBasedSourceSelectionApi\Api;

/**
 * Get all available latitude and longitude objects from address interface.
 *
 * @api
 */
interface GetLatsLngsFromAddressInterface
{
    /**
     * Get all available latitude and longitude objects from address.
     *
     * @param \Magento\InventorySourceSelectionApi\Api\Data\AddressInterface $address
     * @return \Magento\InventoryDistanceBasedSourceSelectionApi\Api\Data\LatLngInterface[]
     * @throws \Magento\InventoryDistanceBasedSourceSelectionApi\Exception\NoSuchLatsLngsFromAddressProviderException
     */
    public function execute(\Magento\InventorySourceSelectionApi\Api\Data\AddressInterface $address): array;
}
