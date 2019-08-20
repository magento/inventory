<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Test\Integration\GetPickupLocationsTest;

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
 * Cover usage of Pickup Location filters, base sort and paging.
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

    protected function setUp()
    {
        $this->getPickupLocations = Bootstrap::getObjectManager()->get(GetPickupLocations::class);
        $this->searchRequestBuilder = Bootstrap::getObjectManager()->get(SearchRequestBuilderInterface::class);
        $this->sortOrderBuilder = Bootstrap::getObjectManager()->get(SortOrderBuilder::class);
    }

    /**
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/sources.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryInStorePickup/Test/_files/source_pickup_location_attributes.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stocks.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stock_source_links.php
     * @magentoDataFixture ../../../../app/code/Magento/InventorySalesApi/Test/_files/websites_with_stores.php
     * @magentoDataFixture ../../../../app/code/Magento/InventorySalesApi/Test/_files/stock_website_sales_channels.php
     *
     * @param array $codeFilter
     * @param array $nameFilter
     * @param string $salesChannelCode
     * @param array $paging
     * @param array $sortOrder
     * @param string[] $sortedSourceCodes
     *
     * @dataProvider executeDataProvider
     * @magentoAppArea frontend
     *
     * @magentoDbIsolation disabled
     */
    public function testExecute(
        array $codeFilter,
        array $nameFilter,
        string $salesChannelCode,
        array $paging,
        array $sortOrder,
        array $sortedSourceCodes
    ): void {
        if (!empty($codeFilter)) {
            $this->searchRequestBuilder->setPickupLocationCodeFilter($codeFilter['value'], $codeFilter['condition']);
        }

        if (!empty($nameFilter)) {
            $this->searchRequestBuilder->setNameFilter($nameFilter['value'], $nameFilter['condition']);
        }

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

        $this->assertEquals(count($sortedSourceCodes), $searchResult->getTotalCount());
        $this->assertCount(count($sortedSourceCodes), $searchResult->getItems());
        foreach ($sortedSourceCodes as $key => $code) {
            $this->assertEquals($code, $searchResult->getItems()[$key]->getSourceCode());
        }
    }

    /**
     * [
     *      Pickup Location Code Filter[
     *          Value,
     *          Condition
     *      ],
     *      Name Filter[
     *          Value,
     *          Condition
     *      ],
     *      Sales Channel Code,
     *      Page[
     *          Page Size,
     *          Current Page
     *      ],
     *      Sort Orders[
     *          Sort Order[
     *              Direction,
     *              Field
     *      ],
     *      Expected Source Codes[]
     * ]
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function executeDataProvider(): array
    {
        return [
            [ /* Data set #0 */
              ['value' => 'eu-1', 'condition' => 'eq'],
              [],
              'eu_website',
              [],
              [],
              ['eu-1']
            ],
            [ /* Data set #1 */
              ['value' => 'eu-1,eu-3', 'condition' => 'in'],
              [],
              'eu_website',
              [],
              [],
              ['eu-1', 'eu-3']
            ],
            [ /* Data set #2 */
              ['value' => 'eu%', 'condition' => 'like'],
              [],
              'global_website',
              [],
              [],
              ['eu-1', 'eu-3']
            ],
            [ /* Data set #3 */
              ['value' => 'u', 'condition' => 'fulltext'],
              [],
              'global_website',
              [],
              [],
              ['eu-1', 'eu-3', 'us-1']
            ],
            [ /* Data set #4 */
              ['value' => 'eu-2', 'condition' => 'eq'],
              [],
              'eu_website',
              [],
              [],
              []
            ],
            [ /* Data set #5 */
              [],
              ['value' => 'EU-source-1', 'condition' => 'eq'],
              'eu_website',
              [],
              [],
              ['eu-1']
            ],
            [ /* Data set #6 */
              [],
              ['value' => 'source', 'condition' => 'fulltext'],
              'global_website',
              [],
              [],
              ['eu-1', 'eu-3', 'us-1']
            ],
            [ /* Data set #7 */
              ['value' => 'eu%', 'condition' => 'like'],
              ['value' => 'source', 'condition' => 'fulltext'],
              'global_website',
              [],
              [],
              ['eu-1', 'eu-3']
            ],
            [ /* Data set #8 */
              [],
              [],
              'global_website',
              [],
              [],
              ['eu-1', 'eu-3', 'us-1']
            ],
            [ /* Data set #9 */
              [],
              [],
              'global_website',
              [],
              [],
              ['eu-1', 'eu-3', 'us-1']
            ],
            [ /* Data set #10 */
              [],
              [],
              'global_website',
              [1, 1],
              [],
              ['eu-1']
            ],
            [ /* Data set #11 */
              [],
              [],
              'global_website',
              [1, 2],
              [],
              ['eu-3']
            ],
            [ /* Data set #12 */
              [],
              [],
              'global_website',
              [],
              [
                  [
                      SortOrder::DIRECTION => SortOrder::SORT_DESC,
                      SortOrder::FIELD => SourceInterface::COUNTRY_ID
                  ]
              ],
              ['us-1', 'eu-1', 'eu-3']
            ],
            [ /* Data set #13 */
              [],
              [],
              'global_website',
              [],
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
            [ /* Data set #14 */
              [],
              [],
              'global_website',
              [1, 2],
              [
                  [
                      SortOrder::DIRECTION => SortOrder::SORT_DESC,
                      SortOrder::FIELD => SourceInterface::COUNTRY_ID
                  ]
              ],
              ['eu-1']
            ],
            [ /* Data set #15 */
              ['value' => 'eu%', 'condition' => 'like'],
              ['value' => 'source', 'condition' => 'fulltext'],
              'global_website',
              [1, 2],
              [
                  [
                      SortOrder::DIRECTION => SortOrder::SORT_DESC,
                      SortOrder::FIELD => SourceInterface::COUNTRY_ID
                  ]
              ],
              ['eu-3']
            ]
        ];
    }
}
