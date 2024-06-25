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
use Magento\InventoryInStorePickupApi\Model\SearchRequestBuilderInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests coverage for @see \Magento\InventoryInStorePickup\Model\GetPickupLocations.
 *
 * Cover usage of base sort and paging.
 */
class GeneralTest extends TestCase
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
     *
     * @param string $salesChannelCode
     * @param array $paging
     * @param int $expectedTotalCount
     * @param array $sortOrder
     * @param string[] $sortedPickupLocationCodes
     *
     * @dataProvider executeDataProvider
     * @magentoAppArea frontend
     *
     * @magentoDbIsolation disabled
     */
    public function testExecute(
        string $salesChannelCode,
        array $paging,
        int $expectedTotalCount,
        array $sortOrder,
        array $sortedPickupLocationCodes
    ): void {

        if (!empty($paging)) {
            $this->searchRequestBuilder->setPageSize(current($paging));
            $this->searchRequestBuilder->setCurrentPage(next($paging));
        }

        if (!empty($sortOrder)) {
            $sorts = [];
            foreach ($sortOrder as $sort) {
                $this->sortOrderBuilder->setField($sort[SortOrder::FIELD]);
                $this->sortOrderBuilder->setDirection($sort[SortOrder::DIRECTION]);
                $sorts[] = $this->sortOrderBuilder->create();
            }
            $this->searchRequestBuilder->setSortOrders($sorts);
        }

        $this->searchRequestBuilder->setScopeCode($salesChannelCode);
        $this->searchRequestBuilder->setScopeType(SalesChannelInterface::TYPE_WEBSITE);

        $searchRequest = $this->searchRequestBuilder->create();
        $searchResult = $this->getPickupLocations->execute($searchRequest);

        $this->assertEquals($expectedTotalCount, $searchResult->getTotalCount());
        $this->assertCount(count($sortedPickupLocationCodes), $searchResult->getItems());
        foreach ($sortedPickupLocationCodes as $key => $code) {
            $this->assertEquals($code, $searchResult->getItems()[$key]->getPickupLocationCode());
        }
    }

    /**
     * [
     *      Sales Channel Code,
     *      Page[
     *          Page Size,
     *          Current Page
     *      ],
     *      Expected Total Count,
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
              'global_website',
              [],
              3,
              [],
              ['eu-1', 'eu-3', 'us-1']
            ],
            [ /* Data set #1 */
              'global_website',
              [],
              3,
              [],
              ['eu-1', 'eu-3', 'us-1']
            ],
            [ /* Data set #2 */
              'global_website',
              [1, 1],
              3,
              [],
              ['eu-1']
            ],
            [ /* Data set #3 */
              'global_website',
              [1, 2],
              3,
              [],
              ['eu-3']
            ],
            [ /* Data set #4 */
              'global_website',
              [],
              3,
              [
                  [
                      SortOrder::DIRECTION => SortOrder::SORT_DESC,
                      SortOrder::FIELD => SourceInterface::COUNTRY_ID
                  ]
              ],
              ['us-1', 'eu-1', 'eu-3']
            ],
            [ /* Data set #5 */
              'global_website',
              [],
              3,
              [
                  [
                      SortOrder::DIRECTION => SortOrder::SORT_DESC,
                      SortOrder::FIELD => SourceInterface::POSTCODE
                  ],
                  [
                      SortOrder::DIRECTION => SortOrder::SORT_ASC,
                      SortOrder::FIELD => SourceInterface::COUNTRY_ID
                  ]
              ],
              ['eu-3', 'eu-1', 'us-1']
            ],
            [ /* Data set #6 */
              'global_website',
              [1, 2],
              3,
              [
                  [
                      SortOrder::DIRECTION => SortOrder::SORT_DESC,
                      SortOrder::FIELD => SourceInterface::COUNTRY_ID
                  ]
              ],
              ['eu-1']
            ],
        ];
    }
}
