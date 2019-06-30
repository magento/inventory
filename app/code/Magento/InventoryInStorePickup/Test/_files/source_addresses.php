<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryApi\Api\SourceRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;

/** @var SourceRepositoryInterface $sourceRepository */
$sourceRepository = Bootstrap::getObjectManager()->get(SourceRepositoryInterface::class);

$sourceAddressMap = [
    'eu-1' => [
        SourceInterface::DESCRIPTION => 'Near Paris',
        SourceInterface::LATITUDE => 48.9833,
        SourceInterface::LONGITUDE => 2.6167,
        SourceInterface::COUNTRY_ID => 'FR',
        SourceInterface::CITY => 'Mitry-Mory',
        SourceInterface::POSTCODE => '77292 CEDEX',
        SourceInterface::STREET => 'Rue Paul Vaillant Couturier 31'
    ],
    'eu-2' => [
        SourceInterface::DESCRIPTION => 'Near Marseille',
        SourceInterface::LATITUDE => 43.5283,
        SourceInterface::LONGITUDE => 5.4497,
        SourceInterface::COUNTRY_ID => 'FR',
        SourceInterface::CITY => 'Aix-en-Provence',
        SourceInterface::POSTCODE => '13100',
        SourceInterface::STREET => 'Rue Marius Reynaud 5'
    ],
    'eu-3' => [
        SourceInterface::DESCRIPTION => 'Near Munich',
        SourceInterface::LATITUDE => 47.8496,
        SourceInterface::LONGITUDE => 12.067,
        SourceInterface::COUNTRY_ID => 'DE',
        SourceInterface::CITY => 'Kolbermoor',
        SourceInterface::POSTCODE => '83059',
        SourceInterface::STREET => 'Rosenheimer Str. 30'
    ],
    'eu-disabled' => [
        SourceInterface::DESCRIPTION => 'In the middle of Germany',
        SourceInterface::LATITUDE => 50.9833,
        SourceInterface::LONGITUDE => 11.0333,
        SourceInterface::COUNTRY_ID => 'DE',
        SourceInterface::CITY => 'Erfurt',
        SourceInterface::POSTCODE => '99098',
        SourceInterface::STREET => 'Juri-Gagarin-Ring 152'
    ],
    'us-1' => [
        SourceInterface::DESCRIPTION => 'In the middle of US',
        SourceInterface::LATITUDE => 38.7634,
        SourceInterface::LONGITUDE => -95.84,
        SourceInterface::COUNTRY_ID => 'US',
        SourceInterface::CITY => 'Burlingame',
        SourceInterface::POSTCODE => '66413',
        SourceInterface::STREET => 'Bloomquist Dr 100'
    ],
];

foreach ($sourceAddressMap as $sourceCode => $addressData) {
    $source = $sourceRepository->get($sourceCode);
    $source->setDescription($addressData[SourceInterface::DESCRIPTION]);
    $source->setLatitude($addressData[SourceInterface::LATITUDE]);
    $source->setLongitude($addressData[SourceInterface::LONGITUDE]);
    $source->setCountryId($addressData[SourceInterface::COUNTRY_ID]);
    $source->setCity($addressData[SourceInterface::CITY]);
    $source->setPostcode($addressData[SourceInterface::POSTCODE]);
    $source->setStreet($addressData[SourceInterface::STREET]);
    $sourceRepository->save($source);
}
