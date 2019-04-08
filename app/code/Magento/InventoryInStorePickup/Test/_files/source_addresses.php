<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\InventoryApi\Api\SourceRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;

/** @var SourceRepositoryInterface $sourceRepository */
$sourceRepository = Bootstrap::getObjectManager()->get(SourceRepositoryInterface::class);

$sourceAddressMap = [
    'eu-1' => [
        'description' => 'Near Paris',
        'latitude' => 48.9833,
        'longitude' => 2.6167,
        'country_id' => 'FR',
        'city' => 'Mitry-Mory',
        'postcode' => '77292 CEDEX',
    ],
    'eu-2' => [
        'description' => 'Near Marseille',
        'latitude' => 43.5283,
        'longitude' => 5.4497,
        'country_id' => 'FR',
        'city' => 'Aix-en-Provence',
        'postcode' => '13080',
    ],
    'eu-3' => [
        'description' => 'Near Munich',
        'latitude' => 47.8496,
        'longitude' => 12.067,
        'country_id' => 'DE',
        'city' => 'Kolbermoor',
        'postcode' => '83059',
    ],
    'eu-disabled' => [
        'description' => 'In the middle of Germany',
        'latitude' => 50.9833,
        'longitude' => 11.0333,
        'country_id' => 'DE',
        'city' => 'Erfurt',
        'postcode' => '99098',
    ],
    'us-1' => [
        'description' => 'In the middle of US',
        'latitude' => 38.7634,
        'longitude' => -95.84,
        'country_id' => 'US',
        'city' => 'Burlingame',
        'postcode' => '66413',
    ],
];

foreach ($sourceAddressMap as $sourceCode => $addressData) {
    $source = $sourceRepository->get($sourceCode);
    $source->setDescription($addressData['description']);
    $source->setLatitude($addressData['latitude']);
    $source->setLongitude($addressData['longitude']);
    $source->setCountryId($addressData['country_id']);
    $source->setCity($addressData['city']);
    $source->setPostcode($addressData['postcode']);
    $sourceRepository->save($source);
}
