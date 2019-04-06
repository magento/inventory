<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupApi\Api;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryApi\Api\Data\SourceInterface;

/**
 * Get nearby sources of a given zip code, based on the given radius in KM.
 *
 * @api
 */
interface GetNearbySourcesByPostcodeInterface
{
    /**
     * Get nearby sources to a given postcode code, based on the given radius in KM
     *
     * @param string $country
     * @param string $postcode
     * @param int $radius
     * @return SourceInterface[]
     *
     * @throws NoSuchEntityException
     */
    public function execute(string $country, string $postcode, int $radius): array;
}
