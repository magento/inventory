<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryImportExport\Test\Unit\Model\Import;

use Magento\InventoryImportExport\Model\Import\SourceItemConvert;
use Magento\InventoryImportExport\Model\Import\Sources;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterfaceFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SourceItemConvertTest extends TestCase
{
    /**
     * @var SourceItemConvert
     */
    private $model;

    /**
     * @var SourceItemInterface|MockObject
     */
    private $sourceItem;

    /**
     * @var SourceItemInterfaceFactory|MockObject
     */
    private $sourceItemFactory;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->sourceItemFactory = $this->createMock(SourceItemInterfaceFactory::class);
        $this->sourceItem = $this->getMockBuilder(SourceItemInterface::class)
            ->getMock();
        $this->sourceItemFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->sourceItem);
        $this->model = new SourceItemConvert($this->sourceItemFactory);
    }

    /**
     * @param array $bunch
     * @param int $expectedStatus
     *
     * @return void
     * @dataProvider dataProviderConvert
     */
    public function testConvert(array $bunch, int $expectedStatus) : void
    {
        $this->sourceItem->expects($this->once())
            ->method('setStatus')
            ->with($expectedStatus);
        $this->model->convert($bunch);
    }

    /**
     * Data provider for callbackValidateProduct test.
     *
     * @return array
     */
    public function dataProviderConvert(): array
    {
        return [
            [
                [
                    [
                        Sources::COL_SOURCE_CODE => 'default',
                        Sources::COL_SKU => 'test',
                        Sources::COL_QTY => -10
                    ],
                ],
                1
            ],
            [
                [
                    [
                        Sources::COL_SOURCE_CODE => 'default',
                        Sources::COL_SKU => 'test',
                        Sources::COL_QTY => 0
                    ],
                ],
                1
            ],
            [
                [
                    [
                        Sources::COL_SOURCE_CODE => 'default',
                        Sources::COL_SKU => 'test',
                        Sources::COL_QTY => 10
                    ],
                ],
                1
            ],
            [
                [
                    [
                        Sources::COL_SOURCE_CODE => 'default',
                        Sources::COL_SKU => 'test',
                        Sources::COL_QTY => -10,
                        Sources::COL_STATUS => 1
                    ],
                ],
                1
            ],
            [
                [
                    [
                        Sources::COL_SOURCE_CODE => 'default',
                        Sources::COL_SKU => 'test',
                        Sources::COL_QTY => 10,
                        Sources::COL_STATUS => 0
                    ],
                ],
                0
            ],
        ];
    }
}
