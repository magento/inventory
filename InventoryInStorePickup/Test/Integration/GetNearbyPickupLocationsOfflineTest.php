<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Test\Integration;

use Magento\InventoryInStorePickup\Model\AddressFactory;
use Magento\InventoryInStorePickup\Model\GetNearbyPickupLocations;
use Magento\InventoryInStorePickupApi\Api\Data\PickupLocationInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class GetNearbyPickupLocationsOfflineTest extends TestCase
{
    /**
     * @var GetNearbyPickupLocations
     */
    private $getNearbyPickupLocations;

    /**
     * @var AddressFactory
     */
    private $addressFactory;

    protected function setUp()
    {
        $this->getNearbyPickupLocations = Bootstrap::getObjectManager()->get(GetNearbyPickupLocations::class);
        $this->addressFactory = Bootstrap::getObjectManager()->get(AddressFactory::class);
    }

    /**
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/sources.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryInStorePickup/Test/_files/source_addresses.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryInStorePickup/Test/_files/source_pickup_location_attributes.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stocks.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stock_source_links.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryInStorePickup/Test/_files/inventory_geoname.php
     * @magentoConfigFixture default/cataloginventory/source_selection_distance_based/provider offline
     *
     * @param array $addressData
     * @param int $radius
     * @param int $stockId
     * @param string[] $sortedSourceCodes
     *
     * @dataProvider executeDataProvider
     * @magentoAppArea frontend
     *
     * @magentoDbIsolation disabled
     */
    public function testExecute(
        array $addressData,
        int $radius,
        int $stockId,
        array $sortedSourceCodes
    ) {
        $address = $this->addressFactory->create($addressData);

        /** @var PickupLocationInterface[] $sources */
        $pickupLocations = $this->getNearbyPickupLocations->execute($address, $radius, $stockId);

        $this->assertCount(count($sortedSourceCodes), $pickupLocations);
        foreach ($sortedSourceCodes as $key => $code) {
            $this->assertEquals($code, $pickupLocations[$key]->getSourceCode());
        }
    }

    /**
     * [
     *      Address[
     *          Country,
     *          Postcode,
     *          Region,
     *          City
     *      ]
     *      Radius (in KM),
     *      Stock Id,
     *      Expected Source Codes[]
     * ]
     *
     * @return array
     */
    public function executeDataProvider(): array
    {
        return [
            [
                [
                    'country' => 'DE',
                    'postcode' => '81671',
                    'region' => null,
                    'city' => null,
                ],
                500,
                10,
                ['eu-3']
            ],
            [
                [
                    'country' => 'FR',
                    'postcode' => null,
                    'region' => 'Bretagne',
                    'city' => null
                ],
                1000,
                10,
                ['eu-1']
            ],
            [
                [
                    'country' => 'FR',
                    'postcode' => null,
                    'region' => null,
                    'city' => 'Saint-Saturnin-lès-Apt'
                ],
                1000,
                30,
                ['eu-1', 'eu-3']
            ],
            [
                [
                    'country' => 'IT',
                    'postcode' => '12022',
                    'region' => null,
                    'city' => null
                    ],
                350,
                10,
                []
            ],
            [
                [
                    'country' => 'IT',
                    'postcode' => '39030',
                    'region' => 'Trentino-Alto Adige',
                    'city' => 'Rasun Di Sotto'
                ],
                350,
                10,
                ['eu-3']
            ],
            [
                [
                    'country' => 'DE',
                    'postcode' => '86559',
                    'region' => null,
                    'city' => null
                ],
                750,
                30,
                ['eu-3', 'eu-1']
            ],
            [
                [
                    'country' => 'US',
                    'postcode' => null,
                    'region' => 'Kansas',
                    'city' => null
                ],
                1000,
                20,
                ['us-1']
            ]
        ];
    }
}
