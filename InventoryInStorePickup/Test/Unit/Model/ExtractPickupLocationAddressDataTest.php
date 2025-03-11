<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Test\Unit\Model;

use Magento\Directory\Model\Region;
use Magento\Directory\Model\RegionFactory;
use Magento\Framework\DataObject\Copy;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\InventoryInStorePickup\Model\ExtractPickupLocationAddressData;
use Magento\InventoryInStorePickup\Model\PickupLocation;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Provide tests for DataResolver
 */
class ExtractPickupLocationAddressDataTest extends TestCase
{
    /**
     * @var ExtractPickupLocationAddressData
     */
    private $model;

    /**
     * @var Copy|MockObject
     */
    private $objectCopyServiceMock;

    /**
     * @var Region|MockObject
     */
    private $regionMock;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $objectManagerHelper = new ObjectManager($this);

        $this->regionMock = $this->getMockBuilder(Region::class)
            ->addMethods(['getCode'])
            ->onlyMethods(['load', 'getName', 'loadByName'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->regionMock->method('loadByName')->willReturnSelf();

        $this->objectCopyServiceMock = $this->getMockBuilder(Copy::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getDataFromFieldset'])
            ->getMockForAbstractClass();

        $regionFactoryMock = $this->getMockBuilder(RegionFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMockForAbstractClass();

        $regionFactoryMock->expects($this->any())
            ->method('create')->willReturn($this->regionMock);

        $this->model = $objectManagerHelper->getObject(
            ExtractPickupLocationAddressData::class,
            [
                'objectCopyService' => $this->objectCopyServiceMock,
                'regionFactory' => $regionFactoryMock,
            ]
        );
    }

    /**
     * Check that region name is replacing correctly
     *
     * @param string $translatedRegionName
     * @param string $expectedRegionName
     * @dataProvider executeDataProvider
     * @return void
     */
    public function testExecute(string $translatedRegionName, string $expectedRegionName): void
    {
        $this->objectCopyServiceMock->method('getDataFromFieldset')
            ->willReturn(['region' => 'original_name']);
        $this->regionMock->method('getName')->willReturn($translatedRegionName);

        $pickupLocation = $this->createMock(PickupLocation::class);
        $pickupLocation->method('getCountryId')->willReturn('US');
        $pickupLocation->method('getRegionId')->willReturn(1);
        $pickupLocation->method('getRegion')->willReturn('original_name');

        $result = $this->model->execute($pickupLocation);

        $this->assertEquals(
            ['region' => $expectedRegionName],
            $result
        );
    }

    /**
     * Provider for testExecute
     *
     * @return array
     */
    public static function executeDataProvider(): array
    {
        return [
            [
                'translatedRegionName' => '',
                'expectedRegionName' => 'original_name',
            ],
            [
                'translatedRegionName' => 'region_name_translated',
                'expectedRegionName' => 'region_name_translated',
            ],
        ];
    }
}
