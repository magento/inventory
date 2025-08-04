<?php
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryDistanceBasedSourceSelectionApi\Api;

/**
 * Get distance between two LatLngRequest points
 *
 * @api
 */
interface GetDistanceInterface
{
    /**
     * Get distance between two points
     *
     * @param \Magento\InventoryDistanceBasedSourceSelectionApi\Api\Data\LatLngInterface $source
     * @param \Magento\InventoryDistanceBasedSourceSelectionApi\Api\Data\LatLngInterface $destination
     * @return float
     */
    public function execute(
        \Magento\InventoryDistanceBasedSourceSelectionApi\Api\Data\LatLngInterface $source,
        \Magento\InventoryDistanceBasedSourceSelectionApi\Api\Data\LatLngInterface $destination
    ): float;
}
