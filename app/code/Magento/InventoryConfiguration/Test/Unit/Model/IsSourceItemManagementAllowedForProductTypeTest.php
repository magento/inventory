<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfiguration\Test\Unit\Model;

use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\InventoryConfiguration\Model\IsSourceItemManagementAllowedForProductType;
use PHPUnit\Framework\TestCase;

class IsSourceItemManagementAllowedForProductTypeTest extends TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $stockConfiguration;

    /**
     * @var IsSourceItemManagementAllowedForProductType
     */
    private $isSourceItemManagementAllowedForProductType;

    protected function setUp()
    {
        $this->stockConfiguration = $this->getMockBuilder(StockConfigurationInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->isSourceItemManagementAllowedForProductType = (new ObjectManager($this))->getObject(
            IsSourceItemManagementAllowedForProductType::class,
            [
                'stockConfiguration' => $this->stockConfiguration,
            ]
        );
    }

    /**
     * @dataProvider executeDataProvider
     */
    public function testExecute($productType, $expectedResult)
    {
        $configData = [
            'simple' => true,
            'virtual' => true,
            'bundle' => false,
            'downloadable' => true,
            'configurable' => false,
            'grouped' => false,
        ];

        $this->stockConfiguration->expects($this->once())
            ->method('getIsQtyTypeIds')
            ->willReturn($configData);

        $this->assertEquals($expectedResult, $this->isSourceItemManagementAllowedForProductType->execute($productType));
    }

    public function executeDataProvider()
    {
        return [
            ['simple', true],
            ['virtual', true],
            ['bundle', false],
            ['downloadable', true],
            ['configurable', false],
            ['grouped', false],
        ];
    }
}
