<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupApi\Test\Fixture;

use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryInStorePickupApi\Api\Data\PickupLocationInterface;

class Source extends \Magento\InventoryApi\Test\Fixture\Source
{
    private const DEFAULT_DATA = [
        SourceInterface::DESCRIPTION => 'Warehouse%uniqid%',
        SourceInterface::LATITUDE => 33.610027,
        SourceInterface::LONGITUDE => -117.694382,
        SourceInterface::EMAIL => 'jessicamoon@warehouse%uniqid%.net',
        SourceInterface::CONTACT_NAME => 'Jessica Moon',
        SourceInterface::PHONE => '3106547744',
        SourceInterface::STREET => '3137 Sumner Street',
        SourceInterface::CITY => 'Irvine',
        SourceInterface::REGION => 'California',
        SourceInterface::REGION_ID => 12,
        SourceInterface::POSTCODE => 92664,
        SourceInterface::COUNTRY_ID => 'US',
        SourceInterface::EXTENSION_ATTRIBUTES_KEY => [
            PickupLocationInterface::IS_PICKUP_LOCATION_ACTIVE => true,
            PickupLocationInterface::FRONTEND_NAME => 'Warehouse%uniqid%',
            PickupLocationInterface::FRONTEND_DESCRIPTION => 'Warehouse%uniqid%',
        ]
    ];

    /**
     * @inheritdoc
     */
    public function apply(array $data = []): ?\Magento\Framework\DataObject
    {
        return parent::apply(array_merge(self::DEFAULT_DATA, $data));
    }
}
