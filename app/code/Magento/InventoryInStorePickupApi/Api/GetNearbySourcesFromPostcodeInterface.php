<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupApi\Api;

/**
 * Get nearby sources of a given zip code, based on the given radius in KM
 *
 * @api
 */
interface GetNearbySourcesFromPostcodeInterface
{
    /**
     * Get nearby sources to a given postcode code, based on the given radius in KM
     *
     * @param string $country
     * @param string $postcode
     * @param int $radius
     * @return array
     */
    public function execute(string $country, string $postcode, int $radius);
}