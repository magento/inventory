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
use Magento\InventoryInStorePickup\Model\SearchRequestBuilder;
use Magento\InventoryInStorePickupApi\Api\Data\PickupLocationInterface;
use Magento\InventoryInStorePickupApi\Api\Data\SearchRequest\DistanceFilterInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Test creation of Search Request @see \Magento\InventoryInStorePickup\Model\SearchRequestBuilder
 */
class SearchRequestBuilderTest extends TestCase
{
    private const VALUE = 'value';
    private const CONDITION_TYPE = 'condition_type';

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
        $this->searchRequestBuilder = Bootstrap::getObjectManager()->get(SearchRequestBuilder::class);
        $this->sortOrderBuilder = Bootstrap::getObjectManager()->get(SortOrderBuilder::class);
    }

    /**
     * Test creation of Search Request.
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testCreate(): void
    {
        $addressStreetFilter = [self::VALUE => 'Some Street', self::CONDITION_TYPE => 'eq'];
        $addressPostcodeFilter = [self::VALUE => '123,456', self::CONDITION_TYPE => 'in'];
        $addressCityFilter = [self::VALUE => 'Zhytomyr', self::CONDITION_TYPE => 'neq'];
        $addressRegionId = [self::VALUE => '14,15', self::CONDITION_TYPE => 'nin'];
        $addressRegion = [self::VALUE => 'Reg%', self::CONDITION_TYPE => 'like'];
        $addressCountry = [self::VALUE => 'U', self::CONDITION_TYPE => 'fulltext'];

        $distanceFilterRadius = 999;
        $distanceFilterCountry = 'UA';
        $distanceFilterRegion = 'Region1';
        $distanceFilterCity = 'Kyiv';
        $distanceFilterPostcode = '100011';

        $scopeCode = 'default_website';
        $scopeType = 'custom_type';

        $nameFilter = [self::VALUE => 'Nam%', self::CONDITION_TYPE => 'like'];
        $codeFilter = [self::VALUE => 'eu-1,eu-2,ua-3', self::CONDITION_TYPE => 'in'];

        $pageSize = 500;
        $currentPage = 200;

        $sortOrders = [
            $this->sortOrderBuilder->setDirection(SortOrder::SORT_ASC)
                                   ->setField(SourceInterface::CITY)
                                   ->create(),
            $this->sortOrderBuilder->setDirection(SortOrder::SORT_DESC)
                                   ->setField(DistanceFilterInterface::DISTANCE_FIELD)
                                   ->create(),
            $this->sortOrderBuilder->setDirection(SortOrder::SORT_ASC)
                                   ->setField(PickupLocationInterface::PICKUP_LOCATION_CODE)
                                   ->create()
        ];

        $this->searchRequestBuilder->setStreetFilter(
            $addressStreetFilter[self::VALUE],
            $addressStreetFilter[self::CONDITION_TYPE]
        )->setPostcodeFilter($addressPostcodeFilter[self::VALUE], $addressPostcodeFilter[self::CONDITION_TYPE])
            ->setCityFilter($addressCityFilter[self::VALUE], $addressCityFilter[self::CONDITION_TYPE])
            ->setRegionIdFilter($addressRegionId[self::VALUE], $addressRegionId[self::CONDITION_TYPE])
            ->setRegionFilter($addressRegion[self::VALUE], $addressRegion[self::CONDITION_TYPE])
            ->setCountryFilter($addressCountry[self::VALUE], $addressCountry[self::CONDITION_TYPE])
            ->setDistanceFilterRadius($distanceFilterRadius)
            ->setDistanceFilterCountry($distanceFilterCountry)
            ->setDistanceFilterRegion($distanceFilterRegion)
            ->setDistanceFilterCity($distanceFilterCity)
            ->setDistanceFilterPostcode($distanceFilterPostcode)
            ->setScopeCode($scopeCode)
            ->setScopeType($scopeType)
            ->setNameFilter($nameFilter[self::VALUE], $nameFilter[self::CONDITION_TYPE])
            ->setPickupLocationCodeFilter($codeFilter[self::VALUE], $codeFilter[self::CONDITION_TYPE])
            ->setPageSize($pageSize)
            ->setCurrentPage($currentPage)
            ->setSortOrders($sortOrders);

        $searchRequest = $this->searchRequestBuilder->create();

        $filterSet = $searchRequest->getFilterSet();
        $this->assertEquals($addressStreetFilter[self::VALUE], $filterSet->getStreetFilter()->getValue());
        $this->assertEquals(
            $addressStreetFilter[self::CONDITION_TYPE],
            $filterSet->getStreetFilter()->getConditionType()
        );
        $this->assertEquals($addressPostcodeFilter[self::VALUE], $filterSet->getPostcodeFilter()->getValue());
        $this->assertEquals(
            $addressPostcodeFilter[self::CONDITION_TYPE],
            $filterSet->getPostcodeFilter()->getConditionType()
        );
        $this->assertEquals($addressCityFilter[self::VALUE], $filterSet->getCityFilter()->getValue());
        $this->assertEquals(
            $addressCityFilter[self::CONDITION_TYPE],
            $filterSet->getCityFilter()->getConditionType()
        );
        $this->assertEquals($addressRegionId[self::VALUE], $filterSet->getRegionIdFilter()->getValue());
        $this->assertEquals(
            $addressRegionId[self::CONDITION_TYPE],
            $filterSet->getRegionIdFilter()->getConditionType()
        );
        $this->assertEquals($addressRegion[self::VALUE], $filterSet->getRegionFilter()->getValue());
        $this->assertEquals(
            $addressRegion[self::CONDITION_TYPE],
            $filterSet->getRegionFilter()->getConditionType()
        );
        $this->assertEquals($addressCountry[self::VALUE], $filterSet->getCountryFilter()->getValue());
        $this->assertEquals(
            $addressCountry[self::CONDITION_TYPE],
            $filterSet->getCountryFilter()->getConditionType()
        );

        $this->assertEquals($codeFilter[self::VALUE], $filterSet->getPickupLocationCodeFilter()->getValue());
        $this->assertEquals(
            $codeFilter[self::CONDITION_TYPE],
            $filterSet->getPickupLocationCodeFilter()->getConditionType()
        );

        $this->assertEquals($nameFilter[self::VALUE], $filterSet->getNameFilter()->getValue());
        $this->assertEquals($nameFilter[self::CONDITION_TYPE], $filterSet->getNameFilter()->getConditionType());

        $distanceFilter = $searchRequest->getDistanceFilter();

        $this->assertEquals($distanceFilterRadius, $distanceFilter->getRadius());
        $this->assertEquals($distanceFilterCity, $distanceFilter->getCity());
        $this->assertEquals($distanceFilterPostcode, $distanceFilter->getPostcode());
        $this->assertEquals($distanceFilterRegion, $distanceFilter->getRegion());
        $this->assertEquals($distanceFilterCountry, $distanceFilter->getCountry());

        $this->assertEquals($scopeCode, $searchRequest->getScopeCode());
        $this->assertEquals($scopeType, $searchRequest->getScopeType());
        $this->assertEquals($currentPage, $searchRequest->getCurrentPage());
        $this->assertEquals($pageSize, $searchRequest->getPageSize());

        foreach ($searchRequest->getSort() as $key => $sortOrder) {
            $this->assertSame($sortOrders[$key], $sortOrder);
        }
    }
}
