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
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests coverage for @see \Magento\InventoryInStorePickup\Model\GetNearbyPickupLocations.
 */
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
     * @magentoDataFixture ../../../../app/code/Magento/InventorySalesApi/Test/_files/websites_with_stores.php
     * @magentoDataFixture ../../../../app/code/Magento/InventorySalesApi/Test/_files/stock_website_sales_channels.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryInStorePickup/Test/_files/inventory_geoname.php
     *
     * @magentoConfigFixture default/cataloginventory/source_selection_distance_based/provider offline
     *
     * @param array $addressData
     * @param int $radius
     * @param int $stockId
     * @param string[] $sortedSourceCodes
     *
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @dataProvider executeDataProvider
     * @magentoAppArea frontend
     *
     * @magentoDbIsolation disabled
     */
    public function testExecute(
        array $addressData,
        int $radius,
        string $salesChannelCode,
        array $sortedSourceCodes
    ) {
        $address = $this->addressFactory->create($addressData);

        /** @var PickupLocationInterface[] $sources */
        $pickupLocations = $this->getNearbyPickupLocations->execute(
            $address,
            $radius,
            SalesChannelInterface::TYPE_WEBSITE,
            $salesChannelCode
        );

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
     *      Sales Channel Code,
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
                    'postcode' => '81671'
                ],
                500,
                'eu_website',
                ['eu-3']
            ],
            [
                [
                    'country' => 'FR',
                    'region' => 'Bretagne'
                ],
                1000,
                'eu_website',
                ['eu-1']
            ],
            [
                [
                    'country' => 'FR',
                    'city' => 'Saint-Saturnin-lès-Apt'
                ],
                1000,
                'global_website',
                ['eu-1', 'eu-3']
            ],
            [
                [
                    'country' => 'IT',
                    'postcode' => '12022'
                    ],
                350,
                'eu_website',
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
                'eu_website',
                ['eu-3']
            ],
            [
                [
                    'country' => 'DE',
                    'postcode' => '86559',
                ],
                750,
                'global_website',
                ['eu-3', 'eu-1']
            ],
            [
                [
                    'country' => 'US',
                    'region' => 'Kansas'
                ],
                1000,
                'us_website',
                ['us-1']
            ]
        ];
    }
}
