<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupSales\Model;

use Magento\Framework\DataObject\Copy;
use Magento\InventoryApi\Api\Data\SourceInterface;

/**
 * Extract Address fields from Source.
 */
class ExtractSourceAddressData
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
     * Extract Address fields from Source.
     *
     * @param SourceInterface $source
     *
     * @return array
     */
    public function execute(SourceInterface $source): array
    {
        return $this->objectCopyService->getDataFromFieldset(
            'inventory_convert_pickup_location',
            'to_in_store_pickup_shipping_address',
            $source
        );
    }
}
