<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Test\Integration;

use Magento\Framework\Api\SortOrder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryInStorePickup\Model\GetPickupLocations;
use Magento\InventoryInStorePickupApi\Api\Data\PickupLocationInterface;
use Magento\InventoryInStorePickupApi\Api\Data\SearchRequest\DistanceFilterInterface;
use Magento\InventoryInStorePickupApi\Model\SearchRequestBuilder;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests coverage for @see \Magento\InventoryInStorePickup\Model\GetPickupLocations.
 */
class GetPickupLocationsDistanceFilterOfflineTest extends TestCase
{
    /**
     * @var GetPickupLocations
     */
    private $getPickupLocations;

    /**
     * @var SearchRequestBuilder
     */
    private $searchRequestBuilder;

    /**
     * @var SortOrderBuilder
     */
    private $sortOrderBuilder;

    protected function setUp()
    {
        $this->getPickupLocations = Bootstrap::getObjectManager()->get(GetPickupLocations::class);
        $this->searchRequestBuilder = Bootstrap::getObjectManager()->get(SearchRequestBuilder::class);
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
     * @param string[] $sortOrder
     * @param string[] $sortedSourceCodes
     *
     * @dataProvider executeDataProvider
     * @magentoAppArea frontend
     *
     * @magentoDbIsolation disabled
     */
    public function testExecute(
        array $searchCriteriaData,
        string $salesChannelCode,
        ?array $sortOrder,
        array $sortedSourceCodes
    ) {
        $this->searchRequestBuilder->setDistanceFilterRadius($searchCriteriaData['radius'])
                                   ->setDistanceFilterCountry($searchCriteriaData['country'])
                                   ->setScopeCode($salesChannelCode);

        if (isset($searchCriteriaData['postcode'])) {
            $this->searchRequestBuilder->setDistanceFilterPostcode($searchCriteriaData['postcode']);
        }

        if (isset($searchCriteriaData['city'])) {
            $this->searchRequestBuilder->setDistanceFilterCity($searchCriteriaData['city']);
        }

        if (isset($searchCriteriaData['region'])) {
            $this->searchRequestBuilder->setDistanceFilterRegion($searchCriteriaData['region']);
        }

        if ($sortOrder) {
            $sorts = [];

            foreach ($sortOrder as $item) {
                $sorts[] = $this->sortOrderBuilder->setField($item[SortOrder::FIELD])
                                         ->setDirection($item[SortOrder::DIRECTION])
                                         ->create();
            }

            $this->searchRequestBuilder->setSortOrders($sorts);
        }

        $searchRequest = $this->searchRequestBuilder->create();

        /** @var PickupLocationInterface[] $sources */
        $result = $this->getPickupLocations->execute(
            $searchRequest
        );

        $this->assertEquals(count($sortedSourceCodes), $result->getTotalCount());
        $this->assertCount(count($sortedSourceCodes), $result->getItems());
        foreach ($sortedSourceCodes as $key => $code) {
            $this->assertEquals($code, $result->getItems()[$key]->getSourceCode());
        }
    }

    /**
     * [
     *      SearchRequestDistanceFilter[
     *          Country,
     *          Postcode,
     *          Region,
     *          City,
     *          Radius (in KM)
     *      ],
     *      Sales Channel Code,
     *      SortOrders[
     *          Direction,
     *          Field
     *      ]
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
              null,
              ['eu-3']
            ],
            [ /* Data set #1 */
              [
                  'country' => 'FR',
                  'region' => 'Bretagne',
                  'radius' => 1000
              ],
              'eu_website',
              null,
              ['eu-1']
            ],
            [ /* Data set #2 */
              [
                  'country' => 'FR',
                  'city' => 'Saint-Saturnin-lès-Apt',
                  'radius' => 1000
              ],
              'global_website',
              [
                  [
                      SortOrder::DIRECTION => SortOrder::SORT_ASC,
                      SortOrder::FIELD => DistanceFilterInterface::DISTANCE_FIELD
                  ]
              ],
              ['eu-1', 'eu-3']
            ],
            [ /* Data set #3 */
              [
                  'country' => 'IT',
                  'postcode' => '12022',
                  'radius' => 350
              ],
              'eu_website',
              [
                  [
                      SortOrder::DIRECTION => SortOrder::SORT_ASC,
                      SortOrder::FIELD => DistanceFilterInterface::DISTANCE_FIELD
                  ]
              ],
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
              null,
              ['eu-3']
            ],
            [ /* Data set #5 */
              [
                  'country' => 'DE',
                  'postcode' => '86559',
                  'radius' => 750
              ],
              'global_website',
              [
                  [
                      SortOrder::DIRECTION => SortOrder::SORT_ASC,
                      SortOrder::FIELD => DistanceFilterInterface::DISTANCE_FIELD
                  ]
              ],
              ['eu-3', 'eu-1']
            ],
            [ /* Data set #6 */
              [
                  'country' => 'US',
                  'region' => 'Kansas',
                  'radius' => 1000
              ],
              'us_website',
              null,
              ['us-1']
            ],
            [ /* Data set #7. Test with descending distance sort. */
              [
                  'country' => 'DE',
                  'postcode' => '86559',
                  'radius' => 750
              ],
              'global_website',
              [
                  [
                      SortOrder::DIRECTION => SortOrder::SORT_DESC,
                      SortOrder::FIELD => DistanceFilterInterface::DISTANCE_FIELD
                  ]
              ],
              ['eu-1', 'eu-3']
            ],
            [ /* Data set #8. Test without distance sort. */
              [
                  'country' => 'FR',
                  'city' => 'Saint-Saturnin-lès-Apt',
                  'radius' => 1000
              ],
              'global_website',
              [
                  [
                      SortOrder::DIRECTION => SortOrder::SORT_ASC,
                      SortOrder::FIELD => SourceInterface::CITY
                  ]
              ],
              ['eu-3', 'eu-1']
            ],
            [ /* Data set #9. Test with multiple sorts. Distance must be in priority. */
              [
                  'country' => 'FR',
                  'city' => 'Saint-Saturnin-lès-Apt',
                  'radius' => 1000
              ],
              'global_website',
              [
                  [
                      SortOrder::DIRECTION => SortOrder::SORT_ASC,
                      SortOrder::FIELD => SourceInterface::CITY
                  ],
                  [
                      SortOrder::DIRECTION => SortOrder::SORT_ASC,
                      SortOrder::FIELD => DistanceFilterInterface::DISTANCE_FIELD
                  ]
              ],
              ['eu-1', 'eu-3']
            ],
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
     * @magentoAppArea frontend
     *
     * @magentoDbIsolation disabled
     */
    public function testExecuteWithPaging()
    {
        $searchRequest = $this->searchRequestBuilder->setDistanceFilterRadius(750)
                                                    ->setDistanceFilterCountry('DE')
                                                    ->setDistanceFilterPostcode('86559')
                                                    ->setScopeCode('global_website')
                                                    ->setPageSize(1)
                                                    ->setCurrentPage(1)
                                                    ->create();

        /** @var PickupLocationInterface[] $sources */
        $result = $this->getPickupLocations->execute(
            $searchRequest
        );

        $this->assertCount(1, $result->getItems());
        $this->assertEquals(1, $result->getTotalCount());
        $this->assertEquals('eu-1', current($result->getItems())->getSourceCode());
    }
}
