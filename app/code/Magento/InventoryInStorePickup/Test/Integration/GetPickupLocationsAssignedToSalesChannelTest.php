<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Test\Integration;

use Magento\InventoryInStorePickup\Model\GetPickupLocationsAssignedToSalesChannel;
use Magento\InventoryInStorePickupApi\Api\Data\PickupLocationInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests coverage for @see \Magento\InventoryInStorePickup\Model\GetPickupLocationsAssignedToSalesChannel.
 */
class GetPickupLocationsAssignedToSalesChannelTest extends TestCase
{
    /**
     * @var GetPickupLocationsAssignedToSalesChannel
     */
    private $getPickupLocations;

    protected function setUp()
    {
        $this->getPickupLocations = Bootstrap::getObjectManager()->get(
            GetPickupLocationsAssignedToSalesChannel::class
        );
    }

    /**
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/sources.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryInStorePickup/Test/_files/source_addresses.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryInStorePickup/Test/_files/source_pickup_location_attributes.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stocks.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stock_source_links.php
     * @magentoDataFixture ../../../../app/code/Magento/InventorySalesApi/Test/_files/websites_with_stores.php
     * @magentoDataFixture ../../../../app/code/Magento/InventorySalesApi/Test/_files/stock_website_sales_channels.php
     *
     * @param string $code
     * @param string[] $sortedSourceCodes
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     * @dataProvider executeDataProvider
     * @magentoAppArea frontend
     *
     * @magentoDbIsolation disabled
     */
    public function testExecute(string $code, array $sortedSourceCodes)
    {
        /** @var PickupLocationInterface[] $sources */
        $pickupLocations = $this->getPickupLocations->execute(SalesChannelInterface::TYPE_WEBSITE, $code);

        $this->assertCount(count($sortedSourceCodes), $pickupLocations);
        foreach ($sortedSourceCodes as $key => $code) {
            $this->assertEquals($code, $pickupLocations[$key]->getSourceCode());
        }
    }

    /**
     * [
     *      Sales Channel Code,
     *      Expected Source Codes[]
     * ]
     * @return array
     */
    public function executeDataProvider(): array
    {
        return [
          ['eu_website', ['eu-1', 'eu-3']],
          ['us_website', ['us-1']],
          ['global_website', ['us-1', 'eu-3', 'eu-1']]
        ];
    }
}
