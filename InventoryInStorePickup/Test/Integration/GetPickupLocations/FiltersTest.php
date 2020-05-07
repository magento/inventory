<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Test\Integration\GetPickupLocations;

use Magento\InventoryInStorePickup\Model\GetPickupLocations;
use Magento\InventoryInStorePickupApi\Model\SearchRequestBuilderInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests coverage for @see \Magento\InventoryInStorePickup\Model\GetPickupLocations.
 *
 * Cover usage of Filter Set.
 */
class FiltersTest extends TestCase
{
    /**
     * @var GetPickupLocations
     */
    private $getPickupLocations;

    /**
     * @var SearchRequestBuilderInterface
     */
    private $searchRequestBuilder;

    protected function setUp(): void
    {
        $this->getPickupLocations = Bootstrap::getObjectManager()->get(GetPickupLocations::class);
        $this->searchRequestBuilder = Bootstrap::getObjectManager()->get(SearchRequestBuilderInterface::class);
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
     * @param array $searchRequestData
     * @param string $salesChannelCode
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
        array $sortedPickupLocationCodes
    ): void {
        foreach ($searchRequestData as $field => $condition) {
            $this->setFilter($field, $condition);
        }

        $searchRequest = $this->searchRequestBuilder->setScopeType(SalesChannelInterface::TYPE_WEBSITE)
            ->setScopeCode($salesChannelCode)
            ->create();

        $searchResult = $this->getPickupLocations->execute($searchRequest);

        $this->assertEquals(count($sortedPickupLocationCodes), $searchResult->getTotalCount());
        $this->assertCount(count($sortedPickupLocationCodes), $searchResult->getItems());
        foreach ($sortedPickupLocationCodes as $key => $code) {
            $this->assertEquals($code, $searchResult->getItems()[$key]->getPickupLocationCode());
        }
    }

    /**
     * Set Filter to the Search Request Builder.
     *
     * @param string $field
     * @param array $condition
     */
    private function setFilter(string $field, array $condition): void
    {
        switch ($field) {
            case 'country':
                $this->searchRequestBuilder->setCountryFilter($condition['value'], $condition['condition']);
                break;
            case 'region':
                $this->searchRequestBuilder->setRegionFilter($condition['value'], $condition['condition']);
                break;
            case 'region_id':
                $this->searchRequestBuilder->setRegionIdFilter($condition['value'], $condition['condition']);
                break;
            case 'city':
                $this->searchRequestBuilder->setCityFilter($condition['value'], $condition['condition']);
                break;
            case 'postcode':
                $this->searchRequestBuilder->setPostcodeFilter($condition['value'], $condition['condition']);
                break;
            case 'street':
                $this->searchRequestBuilder->setStreetFilter($condition['value'], $condition['condition']);
                break;
            case 'pickup_location_code':
                $this->searchRequestBuilder->setPickupLocationCodeFilter($condition['value'], $condition['condition']);
                break;
            case 'name':
                $this->searchRequestBuilder->setNameFilter($condition['value'], $condition['condition']);
                break;
            default:
                throw new \InvalidArgumentException(
                    sprintf('Invalid field provided for Filter Set Test: %s', $field)
                );
        }
    }

    /**
     * [
     *      Filter Set[
     *          Country,
     *          Region,
     *          RegionId,
     *          City,
     *          Postcode,
     *          Street,
     *          Name,
     *          PickupLocationCode
     *      ],
     *      Sales Channel Code,
     *      Expected Pickup Location Codes[]
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
              [
                  'country' => ['value' => 'FR', 'condition' => 'eq']
              ],
              'eu_website',
              ['eu-1']
            ],
            [ /* Data set #1 */
              [
                  'country' => ['value' => 'DE', 'condition' => 'eq']
              ],
              'eu_website',
              ['eu-3']
            ],
            [ /* Data set #2 */
              [
                  'country' => ['value' => 'DE', 'condition' => 'neq']
              ],
              'global_website',
              ['eu-1', 'us-1']
            ],
            [ /* Data set #3 */
              [
                  'country' => ['value' => 'DE,FR', 'condition' => 'in']
              ],
              'global_website',
              ['eu-1', 'eu-3']
            ],
            [ /* Data set #4 */
              [
                  'country' => ['value' => 'DE', 'condition' => 'neq'],
                  'city' => ['value' => 'Mitry-Mory', 'condition' => 'eq']
              ],
              'eu_website',
              ['eu-1']
            ],
            [ /* Data set #5 */
              [
                  'country' => ['value' => 'FR', 'condition' => 'neq'],
                  'city' => ['value' => 'Mitry-Mory', 'condition' => 'eq']
              ],
              'eu_website',
              []
            ],
            [ /* Data set #6 */
              [
                  'city' => ['value' => 'Kolbermoor', 'condition' => 'eq'],
              ],
              'eu_website',
              ['eu-3']
            ],
            [ /* Data set #7 */
              [
                  'country' => ['value' => 'DE', 'condition' => 'eq'],
                  'city' => ['value' => 'Mitry-Mory,Kolbermoor', 'condition' => 'in'],
              ],
              'eu_website',
              ['eu-3']
            ],
            [ /* Data set #8 */
              [
                  'postcode' => ['value' => '66413', 'condition' => 'eq'],
              ],
              'global_website',
              ['us-1']
            ],
            [ /* Data set #9 */
              [
                  'country' => ['value' => 'FR', 'condition' => 'eq'],
                  'postcode' => ['value' => '77292 CEDEX', 'condition' => 'eq'],
              ],
              'eu_website',
              ['eu-1']
            ],
            [ /* Data set #10 */
              [
                  'country' => ['value' => 'FR,DE', 'condition' => 'in'],
                  'postcode' => ['value' => '77292 CEDEX,83059', 'condition' => 'in'],
              ],
              'eu_website',
              ['eu-1', 'eu-3']
            ],
            [ /* Data set #11 */
              [
                  'city' => ['value' => 'Burlingame', 'condition' => 'eq'],
                  'postcode' => ['value' => '66413', 'condition' => 'eq'],
              ],
              'global_website',
              ['us-1']
            ],
            [ /* Data set #12 */
              [
                  'city' => ['value' => 'Burlingame', 'condition' => 'eq'],
                  'postcode' => ['value' => '66413', 'condition' => 'eq'],
              ],
              'eu_website',
              []
            ],
            [ /* Data set #13 */
              [
                  'street' => ['value' => 'Bloomquist Dr 100', 'condition' => 'eq'],
              ],
              'global_website',
              ['us-1']
            ],
            [ /* Data set #14 */
              [
                  'city' => ['value' => 'Mitry-Mory', 'condition' => 'eq'],
                  'street' => ['value' => 'Rue Paul Vaillant Couturier 31', 'condition' => 'eq'],
              ],
              'eu_website',
              ['eu-1']
            ],
            [ /* Data set #15 */
              [
                  'street' => ['value' => 'Rosenheimer%', 'condition' => 'like'],
              ],
              'eu_website',
              ['eu-3']
            ],
            [ /* Data set #16 */
              [
                  'postcode' => ['value' => '77292 CEDEX', 'condition' => 'eq'],
                  'street' => ['value' => 'Rue Paul%', 'condition' => 'like'],
              ],
              'global_website',
              ['eu-1']
            ],
            [ /* Data set #17 */
              [
                  'country' => ['value' => 'US', 'condition' => 'neq'],
                  'city' => ['value' => 'Mitry-Mory', 'condition' => 'eq'],
                  'postcode' => ['value' => '77%', 'condition' => 'like'],
                  'street' => ['value' => 'Rue Paul%', 'condition' => 'like'],
              ],
              'global_website',
              ['eu-1']
            ],
            [ /* Data set #18 */
              [
                  'region_id' => ['value' => '81', 'condition' => 'eq'],
              ],
              'eu_website',
              ['eu-3']
            ],
            [ /* Data set #19 */
              [
                  'region' => ['value' => 'Seine-et-Marne', 'condition' => 'eq'],
              ],
              'eu_website',
              ['eu-1']
            ],
            [ /* Data set #20 */
              [
                  'region' => ['value' => 'California', 'condition' => 'eq'],
                  'region_id' => ['value' => '12', 'condition' => 'eq'],
              ],
              'global_website',
              ['us-1']
            ],
            [ /* Data set #21 */
              [
                  'region' => ['value' => 'California', 'condition' => 'eq'],
                  'region_id' => ['value' => '94', 'condition' => 'eq'],
              ],
              'global_website',
              []
            ],
            [ /* Data set #22 */
              [
                  'country' => ['value' => 'FR', 'condition' => 'neq'],
                  'region' => ['value' => 'Bayern', 'condition' => 'eq'],
                  'region_id' => ['value' => '81', 'condition' => 'eq'],
                  'city' => ['value' => 'K%', 'condition' => 'like'],
                  'postcode' => ['value' => '83059,13100', 'condition' => 'in'],
                  'street' => ['value' => 'heimer', 'condition' => 'fulltext'],
              ],
              'global_website',
              ['eu-3']
            ],
            [ /* Data set #23 */
                [
                    'pickup_location_code' => ['value' => 'eu-1', 'condition' => 'eq']
                ],
                'eu_website',
                ['eu-1']
            ],
            [ /* Data set #24 */
              [
                  'pickup_location_code' => ['value' => 'eu-1,eu-3', 'condition' => 'in']
              ],
              'eu_website',
              ['eu-1', 'eu-3']
            ],
            [ /* Data set #25 */
              [
                  'pickup_location_code' => ['value' => 'eu%', 'condition' => 'like']
              ],
              'global_website',
              ['eu-1', 'eu-3']
            ],
            [ /* Data set #26 */
              [
                  'pickup_location_code' => ['value' => 'u', 'condition' => 'fulltext']
              ],
              'global_website',
              ['eu-1', 'eu-3', 'us-1']
            ],
            [ /* Data set #27 */
              [
                  'pickup_location_code' => ['value' => 'eu-2', 'condition' => 'eq']
              ],
              'eu_website',
              []
            ],
            [ /* Data set #28 */
              [
                  'name' => ['value' => 'EU-source-1', 'condition' => 'eq']
              ],
              'eu_website',
              ['eu-1']
            ],
            [ /* Data set #29 */
              [
                  'name' => ['value' => 'source', 'condition' => 'fulltext']
              ],
              'global_website',
              ['eu-1', 'eu-3', 'us-1']
            ],
            [ /* Data set #30 */
              [
                  'name' => ['value' => 'source', 'condition' => 'fulltext'],
                  'pickup_location_code' => ['value' => 'eu%', 'condition' => 'like']
              ],
              'global_website',
              ['eu-1', 'eu-3']
            ],
            [ /* Data set #31 */
              [
                  'country' => ['value' => 'FR', 'condition' => 'neq'],
                  'region' => ['value' => 'Bayern', 'condition' => 'eq'],
                  'region_id' => ['value' => '81', 'condition' => 'eq'],
                  'city' => ['value' => 'K%', 'condition' => 'like'],
                  'postcode' => ['value' => '83059,13100', 'condition' => 'in'],
                  'street' => ['value' => 'heimer', 'condition' => 'fulltext'],
                  'name' => ['value' => 'source', 'condition' => 'fulltext'],
                  'pickup_location_code' => ['value' => 'eu%', 'condition' => 'like']
              ],
              'global_website',
              ['eu-3']
            ],
        ];
    }
}
