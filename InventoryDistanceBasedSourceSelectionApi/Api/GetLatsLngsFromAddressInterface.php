<?php
/**
 * Copyright 2020 Adobe
 * All Rights Reserved.
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
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute(\Magento\InventorySourceSelectionApi\Api\Data\AddressInterface $address): array;
}
