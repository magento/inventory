<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Test\Integration;

use Magento\InventoryInStorePickup\Model\GetNearbyPickupLocations;
use Magento\InventoryInStorePickupApi\Api\Data\PickupLocationInterface;
use Magento\InventoryInStorePickupApi\Api\Data\SearchCriteriaInterfaceFactory;
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
     * @var SearchCriteriaInterfaceFactory
     */
    private $searchCriteriaFactory;

    protected function setUp()
    {
        $this->getNearbyPickupLocations = Bootstrap::getObjectManager()->get(GetNearbyPickupLocations::class);
        $this->searchCriteriaFactory = Bootstrap::getObjectManager()->get(SearchCriteriaInterfaceFactory::class);
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
     * @param array $searchCriteriaData
     * @param string $salesChannelCode
     * @param string[] $sortedSourceCodes
     *
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @dataProvider executeDataProvider
     * @magentoAppArea frontend
     *
     * @magentoDbIsolation disabled
     */
    public function testExecute(
        array $searchCriteriaData,
        string $salesChannelCode,
        array $sortedSourceCodes
    ) {
        $searchCriteria = $this->searchCriteriaFactory->create();

        $searchCriteria->setRadius($searchCriteriaData['radius']);
        $searchCriteria->setCountry($searchCriteriaData['country']);

        if (isset($searchCriteriaData['postcode'])) {
            $searchCriteria->setPostcode($searchCriteriaData['postcode']);
        }

        if (isset($searchCriteriaData['city'])) {
            $searchCriteria->setCity($searchCriteriaData['city']);
        }

        if (isset($searchCriteriaData['region'])) {
            $searchCriteria->setRegion($searchCriteriaData['region']);
        }

        /** @var PickupLocationInterface[] $sources */
        $pickupLocations = $this->getNearbyPickupLocations->execute(
            $searchCriteria,
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
     *      SearchCriteria[
     *          Country,
     *          Postcode,
     *          Region,
     *          City,
     *          Radius (in KM)
     *      ]
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
                    'postcode' => '81671',
                    'radius' => 500
                ],
                'eu_website',
                ['eu-3']
            ],
            [
                [
                    'country' => 'FR',
                    'region' => 'Bretagne',
                    'radius' => 1000
                ],
                'eu_website',
                ['eu-1']
            ],
            [
                [
                    'country' => 'FR',
                    'city' => 'Saint-Saturnin-lès-Apt',
                    'radius' => 1000
                ],
                'global_website',
                ['eu-1', 'eu-3']
            ],
            [
                [
                    'country' => 'IT',
                    'postcode' => '12022',
                    'radius' => 350
                ],
                'eu_website',
                []
            ],
            [
                [
                    'country' => 'IT',
                    'postcode' => '39030',
                    'region' => 'Trentino-Alto Adige',
                    'city' => 'Rasun Di Sotto',
                    'radius' => 350
                ],
                'eu_website',
                ['eu-3']
            ],
            [
                [
                    'country' => 'DE',
                    'postcode' => '86559',
                    'radius' => 750
                ],
                'global_website',
                ['eu-3', 'eu-1']
            ],
            [
                [
                    'country' => 'US',
                    'region' => 'Kansas',
                    'radius' => 1000
                ],
                'us_website',
                ['us-1']
            ]
        ];
    }
}
