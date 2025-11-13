<?php
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryDistanceBasedSourceSelectionApi\Api;

/**
 * Get latitude and longitude object from address
 *
 * @api
 */
interface GetLatLngFromAddressInterface
{
    /**
     * Get latitude and longitude object from address
     *
     * @param \Magento\InventorySourceSelectionApi\Api\Data\AddressInterface $address
     * @return \Magento\InventoryDistanceBasedSourceSelectionApi\Api\Data\LatLngInterface
     */
    public function execute(
        \Magento\InventorySourceSelectionApi\Api\Data\AddressInterface $address
    ): \Magento\InventoryDistanceBasedSourceSelectionApi\Api\Data\LatLngInterface;
}
