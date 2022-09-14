<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySalesAdminUi\Test\Unit\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\InventoryCatalogApi\Model\IsSingleSourceModeInterface;
use Magento\InventoryConfigurationApi\Model\IsSourceItemManagementAllowedForProductTypeInterface;
use Magento\InventorySalesAdminUi\Model\GetSalableQuantityDataBySku;
use Magento\InventorySalesAdminUi\Model\ResourceModel\GetAssignedStockIdsBySku;
use Magento\InventorySalesAdminUi\Ui\Component\Listing\Column\SalableQuantity;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SalableQuantityTest extends TestCase
{
    /**
     * @var ContextInterface|MockObject
     */
    private $contextMock;

    /**
     * @var UiComponentFactory|MockObject
     */
    private $uiComponentFactoryMock;

    /**
     * @var IsSourceItemManagementAllowedForProductTypeInterface|MockObject
     */
    private $isSourceItemManagementAllowedForProductTypeMock;

    /**
     * @var IsSingleSourceModeInterface|MockObject
     */
    private $isSingleSourceModeMock;

    /**
     * @var GetSalableQuantityDataBySku|MockObject
     */
    private $getSalableQuantityDataBySkuMock;

    /**
     * @var GetAssignedStockIdsBySku|MockObject
     */
    private $getAssignedStockIdsBySkuMock;

    /**
     * @var SalableQuantity
     */
    private $salableQuantity;

    protected function setUp(): void
    {
        $this->contextMock = $this->createMock(ContextInterface::class);
        $this->uiComponentFactoryMock = $this->createMock(UiComponentFactory::class);
        $this->isSourceItemManagementAllowedForProductTypeMock = $this->createMock(
            IsSourceItemManagementAllowedForProductTypeInterface::class
        );
        $this->isSingleSourceModeMock = $this->createMock(IsSingleSourceModeInterface::class);
        $this->getSalableQuantityDataBySkuMock = $this->createMock(GetSalableQuantityDataBySku::class);
        $this->getAssignedStockIdsBySkuMock = $this->createMock(GetAssignedStockIdsBySku::class);

        $this->salableQuantity = new SalableQuantity(
            $this->contextMock,
            $this->uiComponentFactoryMock,
            $this->isSourceItemManagementAllowedForProductTypeMock,
            $this->isSingleSourceModeMock,
            $this->getSalableQuantityDataBySkuMock,
            $this->getAssignedStockIdsBySkuMock,
            2
        );
    }

    public function testPrepareDataSource(): void
    {
        $dataSource = [
            'data' => [
                'totalRecords' => 3,
                'items' => [
                    ['sku' => 'product1', 'type_id' => 'simple'],
                    ['sku' => 'product2', 'type_id' => 'simple'],
                    ['sku' => 'product3', 'type_id' => 'configurable'],
                ],
            ],
        ];

        $this->isSourceItemManagementAllowedForProductTypeMock->expects(self::exactly(3))
            ->method('execute')
            ->withConsecutive(['simple'], ['simple'], ['configurable'])
            ->willReturnOnConsecutiveCalls(true, true, false);
        $this->getAssignedStockIdsBySkuMock->expects(self::exactly(2))
            ->method('execute')
            ->withConsecutive(['product1'], ['product2'])
            ->willReturnOnConsecutiveCalls([2,3], [2,3,4]);
        $this->getSalableQuantityDataBySkuMock->expects(self::once())
            ->method('execute')
            ->with('product1')
            ->willReturn(
                [
                    ['stock_id' => 2, 'stock_name' => 'Stock 2', 'qty' => 200, 'manage_stock' => true],
                    ['stock_id' => 3, 'stock_name' => 'Stock 3', 'qty' => 300, 'manage_stock' => true],
                ]
            );

        $dataSource = $this->salableQuantity->prepareDataSource($dataSource);
        $expectedDataSource = [
            'data' => [
                'totalRecords' => 3,
                'items' => [
                    [
                        'sku' => 'product1',
                        'type_id' => 'simple',
                        'salable_quantity' => [
                            ['stock_id' => 2, 'stock_name' => 'Stock 2', 'qty' => 200, 'manage_stock' => true],
                            ['stock_id' => 3, 'stock_name' => 'Stock 3', 'qty' => 300, 'manage_stock' => true],
                        ],
                    ],
                    [
                        'sku' => 'product2',
                        'type_id' => 'simple',
                        'salable_quantity' => [
                            ['manage_stock' => true, 'message' => 'Associated to 3 stocks']
                        ],
                    ],
                    [
                        'sku' => 'product3',
                        'type_id' => 'configurable',
                        'salable_quantity' => [],
                    ],
                ],
            ],
        ];
        self::assertEquals($expectedDataSource, $dataSource);
    }
}
