<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Test\Integration;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryInStorePickup\Model\GetPickupLocation;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests coverage for @see \Magento\InventoryInStorePickup\Model\GetPickupLocation
 */
class GetPickupLocationTest extends TestCase
{
    private const PICKUP_LOCATION_NOT_FOUND = 'Can not find Pickup Location with code %1 for %2 Sales Channel "%3".';

    /**
     * @var GetPickupLocation
     */
    private $getPickupLocation;

    protected function setUp()
    {
        $this->getPickupLocation = Bootstrap::getObjectManager()->get(
            GetPickupLocation::class
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
     * @param string $pickupLocationCode
     * @param string $salesChannelCode
     * @param bool $exceptionExpected
     * @param string|null $exceptionMessage
     *
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @dataProvider executeDataProvider
     * @magentoAppArea frontend
     *
     * @magentoDbIsolation disabled
     */
    public function testExecute(
        string $pickupLocationCode,
        string $salesChannelCode,
        bool $exceptionExpected,
        ?string $exceptionMessage
    ):void {
        if ($exceptionExpected) {
            $this->expectException(NoSuchEntityException::class);
            $this->expectExceptionMessage($exceptionMessage);
        }

        $pickupLocation = $this->getPickupLocation->execute(
            $pickupLocationCode,
            SalesChannelInterface::TYPE_WEBSITE,
            $salesChannelCode
        );

        if (!$exceptionExpected) {
            $this->assertEquals($pickupLocationCode, $pickupLocation->getSourceCode());
        }
    }

    /**
     * [
     *      Pickup Location Code,
     *      Sales Channel Code,
     *      Exception Expected
     *      Exception Text
     * ]
     *
     * @return array
     */
    public function executeDataProvider(): array
    {
        return [
            [
                'eu-1',
                'eu_website',
                false,
                null
            ],
            [
                'us-1',
                'global_website',
                false,
                null
            ],
            [
                'us-1',
                'eu_website',
                true,
                __(
                    self::PICKUP_LOCATION_NOT_FOUND,
                    [
                        'us-1',
                        SalesChannelInterface::TYPE_WEBSITE,
                        'eu_website'
                    ]
                )
            ],
            [
                'zzz',
                'global_website',
                true,
                __(
                    'Source with code "%value" does not exist.',
                    ['value' => 'zzz']
                )
            ],
            [
                'eu-2',
                'eu_website',
                true,
                __(
                    self::PICKUP_LOCATION_NOT_FOUND,
                    [
                        'eu-2',
                        SalesChannelInterface::TYPE_WEBSITE,
                        'eu_website'
                    ]
                )
            ]
        ];
    }
}
