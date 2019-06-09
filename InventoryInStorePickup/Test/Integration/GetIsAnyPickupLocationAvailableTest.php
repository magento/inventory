<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Test\Integration;

use Magento\InventoryInStorePickup\Model\GetIsAnyPickupLocationAvailable;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests coverage for @see \Magento\InventoryInStorePickup\Model\GetIsAnyPickupLocationAvailable.
 */
class GetIsAnyPickupLocationAvailableTest extends TestCase
{
    /**
     * @var GetIsAnyPickupLocationAvailable
     */
    private $getIsPickupLocationAvailable;

    public function setUp()
    {
        $this->getIsPickupLocationAvailable = Bootstrap::getObjectManager()->get(GetIsAnyPickupLocationAvailable::class);
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
     * @magentoDbIsolation disabled
     */
    public function testExecuteWithAvailableLocations()
    {
        $result = $this->getIsPickupLocationAvailable->execute(SalesChannelInterface::TYPE_WEBSITE, 'eu_website');
        $this->assertTrue($result);
    }

    /**
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/sources.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryInStorePickup/Test/_files/source_addresses.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stocks.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stock_source_links.php
     * @magentoDataFixture ../../../../app/code/Magento/InventorySalesApi/Test/_files/websites_with_stores.php
     * @magentoDataFixture ../../../../app/code/Magento/InventorySalesApi/Test/_files/stock_website_sales_channels.php
     *
     * @magentoDbIsolation disabled
     */
    public function testExecuteWithoutAvailableLocations()
    {
        $result = $this->getIsPickupLocationAvailable->execute(SalesChannelInterface::TYPE_WEBSITE, 'eu_website');
        $this->assertFalse($result);
    }
}
