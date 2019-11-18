<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\InventoryApi\Api\SourceRepositoryInterface;
use Magento\InventoryInStorePickupApi\Api\Data\PickupLocationInterface;
use Magento\TestFramework\Helper\Bootstrap;

/** @var SourceRepositoryInterface $sourceRepository */
$sourceRepository = Bootstrap::getObjectManager()->get(SourceRepositoryInterface::class);

$pickupLocationAttributesMap = [
    'eu-1' => [
        PickupLocationInterface::IS_PICKUP_LOCATION_ACTIVE => true,
        PickupLocationInterface::FRONTEND_NAME => 'EU-source-1'
    ],
    'eu-2' => [
        PickupLocationInterface::IS_PICKUP_LOCATION_ACTIVE => false,
        PickupLocationInterface::FRONTEND_NAME => 'EU-source-2'
    ],
    'eu-3' => [
        PickupLocationInterface::IS_PICKUP_LOCATION_ACTIVE => true,
        PickupLocationInterface::FRONTEND_NAME => 'EU-source-3'
    ],
    'eu-disabled' => [
        PickupLocationInterface::IS_PICKUP_LOCATION_ACTIVE => false,
        PickupLocationInterface::FRONTEND_NAME => 'EU-source-disabled'
    ],
    'us-1' => [
        PickupLocationInterface::IS_PICKUP_LOCATION_ACTIVE => true,
        PickupLocationInterface::FRONTEND_NAME => 'US-source-1'
    ]
];

foreach ($pickupLocationAttributesMap as $sourceCode => $value) {
    $source = $sourceRepository->get($sourceCode);
    $extension = $source->getExtensionAttributes();
    $extension->setIsPickupLocationActive($value[PickupLocationInterface::IS_PICKUP_LOCATION_ACTIVE]);
    $extension->setFrontendName($value[PickupLocationInterface::FRONTEND_NAME]);
    $sourceRepository->save($source);
}
