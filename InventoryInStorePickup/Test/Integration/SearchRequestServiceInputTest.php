<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Test\Integration;

use Magento\Framework\Api\SortOrder;
use Magento\Framework\Webapi\ServiceInputProcessor;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryInStorePickupApi\Api\Data\SearchRequest\AreaInterface;
use Magento\InventoryInStorePickupApi\Api\Data\SearchRequestInterface;
use Magento\InventoryInStorePickupApi\Api\GetPickupLocationsInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Test conversion of web API input into Search Request @see \Magento\InventoryInStorePickup\Model\SearchRequest
 */
class SearchRequestServiceInputTest extends TestCase
{
    /**
     * @var ServiceInputProcessor
     */
    private $serviceInputProcessor;

    protected function setUp(): void
    {
        $this->serviceInputProcessor = Bootstrap::getObjectManager()->get(ServiceInputProcessor::class);
    }

    /**
     * Sort orders from the request must reach the Search Request.
     *
     * @magentoAppArea webapi_rest
     *
     * @return void
     */
    public function testSortIsSetFromRequestData(): void
    {
        $inputData = [
            'searchRequest' => [
                'scopeCode' => 'base',
                'scopeType' => 'website',
                'sort' => [
                    [
                        SortOrder::FIELD => SourceInterface::SOURCE_CODE,
                        SortOrder::DIRECTION => SortOrder::SORT_ASC,
                    ],
                    [
                        SortOrder::FIELD => AreaInterface::DISTANCE_FIELD,
                        SortOrder::DIRECTION => SortOrder::SORT_DESC,
                    ],
                ],
            ],
        ];

        [$searchRequest] = $this->serviceInputProcessor->process(
            GetPickupLocationsInterface::class,
            'execute',
            $inputData
        );

        $this->assertInstanceOf(SearchRequestInterface::class, $searchRequest);
        $sort = $searchRequest->getSort();
        $this->assertCount(2, $sort);
        $this->assertContainsOnlyInstancesOf(SortOrder::class, $sort);
        $this->assertSame(SourceInterface::SOURCE_CODE, $sort[0]->getField());
        $this->assertSame(SortOrder::SORT_ASC, $sort[0]->getDirection());
        $this->assertSame(AreaInterface::DISTANCE_FIELD, $sort[1]->getField());
        $this->assertSame(SortOrder::SORT_DESC, $sort[1]->getDirection());
    }
}
