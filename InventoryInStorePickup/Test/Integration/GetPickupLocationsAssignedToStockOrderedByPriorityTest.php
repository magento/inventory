<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Test\Integration;

use Magento\InventoryInStorePickupApi\Api\Data\PickupLocationInterface;
use Magento\InventoryInStorePickup\Model\GetPickupLocationsAssignedToStockOrderedByPriority;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class GetPickupLocationsAssignedToStockOrderedByPriorityTest extends TestCase
{
    /**
     * @var GetPickupLocationsAssignedToStockOrderedByPriority
     */
    private $getPickupLocations;

    protected function setUp()
    {
        $this->getPickupLocations = Bootstrap::getObjectManager()->get(
            GetPickupLocationsAssignedToStockOrderedByPriority::class
        );
    }

    /**
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/sources.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryInStorePickup/Test/_files/source_addresses.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryInStorePickup/Test/_files/source_pickup_location_attributes.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stocks.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stock_source_links.php
     *
     * @param int $stockId
     * @param string[] $sortedSourceCodes
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     * @dataProvider executeDataProvider
     * @magentoAppArea frontend
     *
     * @magentoDbIsolation disabled
     */
    public function testExecute(int $stockId, array $sortedSourceCodes)
    {
        /** @var PickupLocationInterface[] $sources */
        $pickupLocations = $this->getPickupLocations->execute($stockId);

        $this->assertCount(count($sortedSourceCodes), $pickupLocations);
        foreach ($sortedSourceCodes as $key => $code) {
            $this->assertEquals($code, $pickupLocations[$key]->getSourceCode());
        }
    }

    /**
     * [
     *      Stock Id,
     *      Expected Source Codes[]
     * ]
     * @return array
     */
    public function executeDataProvider(): array
    {
        return [
          [10, ['eu-1', 'eu-3']],
          [20, ['us-1']],
          [30, ['us-1', 'eu-3', 'eu-1']]
        ];
    }
}
