<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Test\Unit\Model\PickupLocation;

use Magento\Directory\Model\Region;
use Magento\Directory\Model\RegionFactory;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\InventoryInStorePickup\Model\PickupLocation;
use Magento\InventoryInStorePickup\Model\PickupLocation\DataResolver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Provide tests for DataResolver
 */
class DataResolverTest extends TestCase
{
    /**
     * @var Region|MockObject
     */
    private $regionMock;

    /**
     * @var DataResolver
     */
    private $model;

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

        $regionFactoryMock = $this->getMockBuilder(RegionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMockForAbstractClass();

        $regionFactoryMock->expects($this->any())
            ->method('create')->willReturn($this->regionMock);

        $this->model = $objectManagerHelper->getObject(
            DataResolver::class,
            ['regionFactory' => $regionFactoryMock]
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
        $this->regionMock->method('getName')->willReturn($translatedRegionName);

        $pickupLocation = $this->createMock(PickupLocation::class);

        $result = $this->model->execute($pickupLocation, ['region' => 'original_name']);

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
    public function executeDataProvider(): array
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
