<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Test\Integration\GetPickupLocations;

use Magento\Framework\Api\SortOrder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryInStorePickup\Model\GetPickupLocations;
use Magento\InventoryInStorePickupApi\Api\Data\PickupLocationInterface;
use Magento\InventoryInStorePickupApi\Api\Data\SearchRequest\AreaInterface;
use Magento\InventoryInStorePickupApi\Model\SearchRequestBuilderInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests coverage for @see \Magento\InventoryInStorePickup\Model\GetPickupLocations.
 *
 * Cover usage of Distance Filter.
 */
class DistanceFilterOfflineTest extends TestCase
{
    /**
     * @var GetPickupLocations
     */
    private $getPickupLocations;

    /**
     * @var SearchRequestBuilderInterface
     */
    private $searchRequestBuilder;

    /**
     * @var SortOrderBuilder
     */
    private $sortOrderBuilder;

    protected function setUp(): void
    {
        $this->getPickupLocations = Bootstrap::getObjectManager()->get(GetPickupLocations::class);
        $this->searchRequestBuilder = Bootstrap::getObjectManager()->get(SearchRequestBuilderInterface::class);
        $this->sortOrderBuilder = Bootstrap::getObjectManager()->get(SortOrderBuilder::class);
    }

    /**
     * @magentoDataFixture Magento_InventoryApi::Test/_files/sources.php
     * @magentoDataFixture Magento_InventoryInStorePickupApi::Test/_files/source_addresses.php
     * @magentoDataFixture Magento_InventoryInStorePickupApi::Test/_files/source_pickup_location_attributes.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stocks.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stock_source_links.php
     * @magentoDataFixture Magento_InventorySalesApi::Test/_files/websites_with_stores.php
     * @magentoDataFixture Magento_InventorySalesApi::Test/_files/stock_website_sales_channels.php
     * @magentoDataFixture Magento_InventoryInStorePickupApi::Test/_files/inventory_geoname.php
     *
     * @magentoConfigFixture default/cataloginventory/source_selection_distance_based/provider offline
     *
     * @param array $searchRequestData
     * @param string $salesChannelCode
     * @param string[] $sortOrder
     * @param string[] $sortedPickupLocationCodes
     *
     * @dataProvider executeDataProvider
     * @magentoAppArea frontend
     *
     * @magentoDbIsolation disabled
     */
    public function testExecute(
        array $searchRequestData,
        string $salesChannelCode,
        ?array $sortOrder,
        array $sortedPickupLocationCodes
    ) {
        $this->searchRequestBuilder->setAreaRadius($searchRequestData['radius'])
                                   ->setScopeCode($salesChannelCode);
        $searchTerm = '';

        if (isset($searchRequestData['postcode'])) {
            $searchTerm = $searchRequestData['postcode'];
        }

        if (isset($searchRequestData['city'])) {
            $searchTerm = $searchRequestData['city'];
        }

        $searchTerm .= ':' . $searchRequestData['country'];
        $this->searchRequestBuilder->setAreaSearchTerm($searchTerm);

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
        $result = $this->getPickupLocations->execute($searchRequest);

        $this->assertEquals(count($sortedPickupLocationCodes), $result->getTotalCount());
        $this->assertCount(count($sortedPickupLocationCodes), $result->getItems());
        foreach ($sortedPickupLocationCodes as $key => $code) {
            $this->assertEquals($code, $result->getItems()[$key]->getPickupLocationCode());
        }
    }

    /**
     * [
     *      Search Request Distance Filter[
     *          Country,
     *          Postcode,
     *          Region,
     *          City,
     *          Radius (in KM)
     *      ],
     *      Sales Channel Code,
     *      Sort Orders[
     *          Sort Order[
     *              Direction,
     *              Field
     *      ],
     *      Expected Pickup Location Codes[]
     * ]
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public static function executeDataProvider(): array
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
                  'city' => 'Saint-Saturnin-lès-Apt',
                  'radius' => 1000
              ],
              'global_website',
              [
                  [
                      SortOrder::DIRECTION => SortOrder::SORT_ASC,
                      SortOrder::FIELD => AreaInterface::DISTANCE_FIELD
                  ]
              ],
              ['eu-1', 'eu-3']
            ],
            [ /* Data set #2 */
              [
                  'country' => 'IT',
                  'postcode' => '12022',
                  'radius' => 350
              ],
              'eu_website',
              [
                  [
                      SortOrder::DIRECTION => SortOrder::SORT_ASC,
                      SortOrder::FIELD => AreaInterface::DISTANCE_FIELD
                  ]
              ],
              []
            ],
            [ /* Data set #3 */
              [
                  'country' => 'IT',
                  'postcode' => '39030',
                  'city' => 'Rasun Di Sotto',
                  'radius' => 350
              ],
              'eu_website',
              null,
              ['eu-3']
            ],
            [ /* Data set #4 */
              [
                  'country' => 'DE',
                  'postcode' => '86559',
                  'radius' => 750
              ],
              'global_website',
              [
                  [
                      SortOrder::DIRECTION => SortOrder::SORT_ASC,
                      SortOrder::FIELD => AreaInterface::DISTANCE_FIELD
                  ]
              ],
              ['eu-3', 'eu-1']
            ],
            [ /* Data set #5. Test with descending distance sort. */
              [
                  'country' => 'DE',
                  'postcode' => '86559',
                  'radius' => 750
              ],
              'global_website',
              [
                  [
                      SortOrder::DIRECTION => SortOrder::SORT_DESC,
                      SortOrder::FIELD => AreaInterface::DISTANCE_FIELD
                  ]
              ],
              ['eu-1', 'eu-3']
            ],
            [ /* Data set #6. Test without distance sort. */
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
            [ /* Data set #7. Test with multiple sorts. Distance must be in priority. */
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
                      SortOrder::FIELD => AreaInterface::DISTANCE_FIELD
                  ]
              ],
              ['eu-1', 'eu-3']
            ],
        ];
    }
}
