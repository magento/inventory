<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Test\Integration;

use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryInStorePickup\Model\GetNearbyPickupLocations;
use Magento\InventoryInStorePickupApi\Api\Data\PickupLocationInterface;
use Magento\InventoryInStorePickupApi\Model\SearchCriteriaBuilder;
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
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var SortOrderBuilder
     */
    private $sortOrderBuilder;

    protected function setUp()
    {
        $this->getNearbyPickupLocations = Bootstrap::getObjectManager()->get(GetNearbyPickupLocations::class);
        $this->searchCriteriaBuilder = Bootstrap::getObjectManager()->get(SearchCriteriaBuilder::class);
        $this->sortOrderBuilder = Bootstrap::getObjectManager()->get(SortOrderBuilder::class);
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
     * @throws NoSuchEntityException
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
        $this->searchCriteriaBuilder->setRadius($searchCriteriaData['radius'])
                                    ->setCountry($searchCriteriaData['country']);

        if (isset($searchCriteriaData['postcode'])) {
            $this->searchCriteriaBuilder->setPostcode($searchCriteriaData['postcode']);
        }

        if (isset($searchCriteriaData['city'])) {
            $this->searchCriteriaBuilder->setCity($searchCriteriaData['city']);
        }

        if (isset($searchCriteriaData['region'])) {
            $this->searchCriteriaBuilder->setRegion($searchCriteriaData['region']);
        }

        $searchCriteria = $this->searchCriteriaBuilder->create();

        /** @var PickupLocationInterface[] $sources */
        $result = $this->getNearbyPickupLocations->execute(
            $searchCriteria,
            SalesChannelInterface::TYPE_WEBSITE,
            $salesChannelCode
        );

        $this->assertEquals(count($sortedSourceCodes), $result->getTotalCount());
        $this->assertCount(count($sortedSourceCodes), $result->getItems());
        foreach ($sortedSourceCodes as $key => $code) {
            $this->assertEquals($code, $result->getItems()[$key]->getSourceCode());
        }
    }

    /**
     * [
     *      GetNearbyLocationsCriteria[
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
            [ /* Data set #0 */
                [
                    'country' => 'DE',
                    'postcode' => '81671',
                    'radius' => 500
                ],
                'eu_website',
                ['eu-3']
            ],
            [ /* Data set #1 */
                [
                    'country' => 'FR',
                    'region' => 'Bretagne',
                    'radius' => 1000
                ],
                'eu_website',
                ['eu-1']
            ],
            [ /* Data set #2 */
                [
                    'country' => 'FR',
                    'city' => 'Saint-Saturnin-lès-Apt',
                    'radius' => 1000
                ],
                'global_website',
                ['eu-1', 'eu-3']
            ],
            [ /* Data set #3 */
                [
                    'country' => 'IT',
                    'postcode' => '12022',
                    'radius' => 350
                ],
                'eu_website',
                []
            ],
            [ /* Data set #4 */
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
            [ /* Data set #5 */
                [
                    'country' => 'DE',
                    'postcode' => '86559',
                    'radius' => 750
                ],
                'global_website',
                ['eu-3', 'eu-1']
            ],
            [ /* Data set #6 */
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
     * @throws NoSuchEntityException
     * @magentoAppArea frontend
     *
     * @magentoDbIsolation disabled
     */
    public function testExecuteWithPaging()
    {
        $searchCriteria = $this->searchCriteriaBuilder->setRadius(750)
                                                      ->setCountry('DE')
                                                      ->setPostcode('86559')
                                                      ->setPageSize(1)
                                                      ->setCurrentPage(1)
                                                      ->create();

        /** @var PickupLocationInterface[] $sources */
        $result = $this->getNearbyPickupLocations->execute(
            $searchCriteria,
            SalesChannelInterface::TYPE_WEBSITE,
            'global_website'
        );

        $this->assertCount(1, $result->getItems());
        $this->assertEquals(1, $result->getTotalCount());
        $this->assertEquals('eu-1', current($result->getItems())->getSourceCode());
    }
}
