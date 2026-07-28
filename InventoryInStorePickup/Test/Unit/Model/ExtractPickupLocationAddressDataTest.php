<?php
/**
 * Copyright 2020 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Test\Unit\Model;

use Magento\Directory\Model\Region;
use Magento\Directory\Model\RegionFactory;
use Magento\Directory\Model\ResourceModel\Region as RegionResource;
use Magento\Framework\DataObject\Copy;
use Magento\Framework\TestFramework\Unit\Helper\MockCreationTrait;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\InventoryInStorePickup\Model\ExtractPickupLocationAddressData;
use Magento\InventoryInStorePickup\Model\PickupLocation;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Provide tests for DataResolver
 */
class ExtractPickupLocationAddressDataTest extends TestCase
{
    use MockCreationTrait;

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
     * @var RegionResource|MockObject
     */
    private $regionResourceMock;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $objectManagerHelper = new ObjectManager($this);

        $this->regionMock = $this->createPartialMockWithReflection(
            Region::class,
            ['getCode', 'getName', 'loadByName']
        );
        $this->regionMock->method('loadByName')->willReturnSelf();

        $this->regionResourceMock = $this->getMockBuilder(RegionResource::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['load'])
            ->getMock();
        $this->regionResourceMock->method('load')->willReturnSelf();

        $this->objectCopyServiceMock = $this->getMockBuilder(Copy::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getDataFromFieldset'])
            ->getMock();

        $regionFactoryMock = $this->getMockBuilder(RegionFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();

        $regionFactoryMock->expects($this->any())
            ->method('create')->willReturn($this->regionMock);

        $this->model = $objectManagerHelper->getObject(
            ExtractPickupLocationAddressData::class,
            [
                'objectCopyService' => $this->objectCopyServiceMock,
                'regionFactory' => $regionFactoryMock,
                'regionResource' => $this->regionResourceMock,
            ]
        );
    }

    /**
     * Check that region name is replacing correctly
     *
     * @param string $translatedRegionName
     * @param string $expectedRegionName
     * @return void
     */
    #[DataProvider('executeDataProvider')]
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

    /**
     * When region_id is set, region is loaded via ResourceModel by ID and loadByName is not called.
     */
    public function testExecuteLoadsRegionByIdWhenRegionIdIsSet(): void
    {
        $this->objectCopyServiceMock->method('getDataFromFieldset')
            ->willReturn(['region' => 'stale_name']);
        $this->regionMock->method('getName')->willReturn('current_name');

        $pickupLocation = $this->createMock(PickupLocation::class);
        $pickupLocation->method('getCountryId')->willReturn('CH');
        $pickupLocation->method('getRegionId')->willReturn(42);
        $pickupLocation->method('getRegion')->willReturn('stale_name');

        $this->regionResourceMock->expects($this->once())
            ->method('load')
            ->with($this->regionMock, 42);
        $this->regionMock->expects($this->never())->method('loadByName');

        $result = $this->model->execute($pickupLocation);

        $this->assertEquals(['region' => 'current_name'], $result);
    }

    /**
     * When region_id is null, region is resolved via loadByName and ResourceModel load is not called.
     */
    public function testExecuteFallsBackToLoadByNameWhenRegionIdIsNull(): void
    {
        $this->objectCopyServiceMock->method('getDataFromFieldset')
            ->willReturn(['region' => 'some_region']);
        $this->regionMock->method('getName')->willReturn('some_region');

        $pickupLocation = $this->createMock(PickupLocation::class);
        $pickupLocation->method('getCountryId')->willReturn('US');
        $pickupLocation->method('getRegionId')->willReturn(null);
        $pickupLocation->method('getRegion')->willReturn('some_region');

        $this->regionResourceMock->expects($this->never())->method('load');
        $this->regionMock->expects($this->once())
            ->method('loadByName')
            ->with('some_region', 'US')
            ->willReturnSelf();

        $result = $this->model->execute($pickupLocation);

        $this->assertEquals(['region' => 'some_region'], $result);
    }

    /**
     * After a Directory data patch renames a subdivision, the current name is resolved
     * live from Directory by region_id, not from the stale string in inventory_source.region.
     */
    public function testExecuteReturnsCurrentRegionNameAfterDirectoryRename(): void
    {
        // inventory_source.region still holds the old name from before the data patch
        $this->objectCopyServiceMock->method('getDataFromFieldset')
            ->willReturn(['region' => 'Friburg']);

        // ResourceModel returns the updated name after the rename
        $this->regionMock->method('getName')->willReturn('Renamed Friburg');

        $pickupLocation = $this->createMock(PickupLocation::class);
        $pickupLocation->method('getCountryId')->willReturn('CH');
        $pickupLocation->method('getRegionId')->willReturn(42);
        $pickupLocation->method('getRegion')->willReturn('Friburg');

        $this->regionResourceMock->expects($this->once())
            ->method('load')
            ->with($this->regionMock, 42);

        $result = $this->model->execute($pickupLocation);

        // Must reflect the live Directory name, not the stale inventory_source value
        $this->assertEquals(['region' => 'Renamed Friburg'], $result);
    }
}
