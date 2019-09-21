<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model;

use Magento\Framework\DataObject\Copy;
use Magento\InventoryApi\Api\Data\SourceInterface;

/**
 * Extract Shipping Address fields from Source.
 */
class ExtractSourceShippingAddressData
{
    /**
     * @var Copy
     */
    private $objectCopyService;

    /**
     * @param Copy $copyService
     */
    public function __construct(Copy $copyService)
    {
        $this->objectCopyService = $copyService;
    }

    /**
     * Extract Shipping Address fields from Source.
     *
     * @TODO Refactor when issue will be resolved in core.
     * @see Please check issue in core for more details: https://github.com/magento/magento2/issues/23386.
     *
     * @param SourceInterface $source
     *
     * @return array
     */
    public function execute(SourceInterface $source): array
    {
        return $this->objectCopyService->copyFieldsetToTarget(
            'inventory_convert_pickup_location',
            'to_pickup_location_shipping_address',
            $source,
            []
        );
    }
}
