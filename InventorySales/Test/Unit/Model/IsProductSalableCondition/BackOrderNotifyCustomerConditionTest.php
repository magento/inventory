<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Test\Unit\Model\IsProductSalableCondition;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\InventoryConfigurationApi\Api\Data\StockItemConfigurationInterface;
use Magento\InventoryConfigurationApi\Api\GetStockItemConfigurationInterface;
use Magento\InventorySales\Model\IsProductSalableCondition\BackOrderNotifyCustomerCondition;
use Magento\InventorySalesApi\Api\Data\ProductSalabilityErrorInterface;
use Magento\InventorySalesApi\Api\Data\ProductSalabilityErrorInterfaceFactory;
use Magento\InventorySalesApi\Api\Data\ProductSalableResultInterface;
use Magento\InventorySalesApi\Api\Data\ProductSalableResultInterfaceFactory;
use Magento\InventorySalesApi\Model\GetStockItemDataInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test backorder notification
 */
class BackOrderNotifyCustomerConditionTest extends TestCase
{
    /**
     * @var BackOrderNotifyCustomerCondition
     */
    private $model;
    /**
     * @var StockItemConfigurationInterface|MockObject
     */
    private $stockItemConfiguration;
    /**
     * @var array|null
     */
    private $stockItemData;

    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        parent::setUp();
        $objectManager = new ObjectManager($this);
        $productSalableResultFactory = $this->createMock(
            ProductSalableResultInterfaceFactory::class
        );
        $productSalableResultFactory->method('create')
            ->willReturnCallback(
                function ($args) {
                    $mock = $this->getMockForAbstractClass(ProductSalableResultInterface::class);
                    $mock->method('getErrors')->willReturn($args['errors'] ?? []);
                    $mock->method('isSalable')->willReturn(empty($args['errors']));
                    return $mock;
                }
            );
        $productSalabilityErrorFactory = $this->createMock(
            ProductSalabilityErrorInterfaceFactory::class
        );
        $productSalabilityErrorFactory->method('create')
            ->willReturnCallback(
                function ($args) {
                    $mock = $this->getMockForAbstractClass(ProductSalabilityErrorInterface::class);
                    $mock->method('getCode')->willReturn($args['code'] ?? null);
                    $mock->method('getMessage')->willReturn($args['message'] ?? null);
                    return $mock;
                }
            );
        $getStockItemConfiguration = $this->getMockForAbstractClass(
            GetStockItemConfigurationInterface::class
        );
        $this->stockItemConfiguration = $this->getMockForAbstractClass(
            StockItemConfigurationInterface::class
        );
        $getStockItemConfiguration->method('execute')->willReturnCallback(
            function () {
                return $this->stockItemConfiguration;
            }
        );
        $getStockItemData = $this->getMockForAbstractClass(
            GetStockItemDataInterface::class
        );
        $getStockItemData->method('execute')->willReturnCallback(
            function () {
                return $this->stockItemData;
            }
        );
        $this->model = $objectManager->getObject(
            BackOrderNotifyCustomerCondition::class,
            [
                'getStockItemConfiguration' => $getStockItemConfiguration,
                'getStockItemData' => $getStockItemData,
                'productSalableResultFactory' => $productSalableResultFactory,
                'productSalabilityErrorFactory' => $productSalabilityErrorFactory,
            ]
        );
    }

    /**
     * Test execute with different stock settings
     *
     * @dataProvider executeDataProvider
     * @param array|null $stockData
     * @param int $reqQty
     * @param int $backOrders
     * @param bool $manageStock
     * @param array $errors
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function testExecute(?array $stockData, int $reqQty, int $backOrders, bool $manageStock, array $errors): void
    {
        $this->stockItemConfiguration->method('isManageStock')
            ->willReturn($manageStock);
        $this->stockItemConfiguration->method('getBackorders')
            ->willReturn($backOrders);
        $this->stockItemData = $stockData;
        $actualErrors = [];
        foreach ($this->model->execute('simple', 1, $reqQty)->getErrors() as $error) {
            $actualErrors[] = ['code' => $error->getCode(), 'message' => $error->getMessage()];
        }
        $this->assertEquals($errors, $actualErrors);
    }

    /**
     * @return array
     */
    public function executeDataProvider(): array
    {
        return [
            'StockQty=0, ReqQty=1, Backorders=YesNotify, ManageStock=Yes' => [
                [
                    GetStockItemDataInterface::QUANTITY => 0,
                ],
                1,
                StockItemConfigurationInterface::BACKORDERS_YES_NOTIFY,
                true,
                [
                    [
                        'code' => 'back_order-not-enough',
                        'message' => 'We don\'t have as many quantity as you requested,'
                            . ' but we\'ll back order the remaining 1.'
                    ]
                ],
            ],
            'StockQty=1, ReqQty=1, Backorders=YesNotify, ManageStock=Yes' => [
                [
                    GetStockItemDataInterface::QUANTITY => 1,
                ],
                1,
                StockItemConfigurationInterface::BACKORDERS_YES_NOTIFY,
                true,
                [],
            ],
            'StockQty=?, ReqQty=1, Backorders=YesNotify, ManageStock=Yes' => [
                null,
                1,
                StockItemConfigurationInterface::BACKORDERS_YES_NOTIFY,
                true,
                [],
            ],
            'StockQty=0, ReqQty=1, Backorders=No, ManageStock=Yes' => [
                [
                    GetStockItemDataInterface::QUANTITY => 0,
                ],
                1,
                StockItemConfigurationInterface::BACKORDERS_NO,
                true,
                [],
            ],
            'StockQty=0, ReqQty=1, Backorders=Yes, ManageStock=Yes' => [
                [
                    GetStockItemDataInterface::QUANTITY => 0,
                ],
                1,
                StockItemConfigurationInterface::BACKORDERS_YES_NONOTIFY,
                true,
                [],
            ],
            'StockQty=0, ReqQty=1, Backorders=YesNotify, ManageStock=No' => [
                [
                    GetStockItemDataInterface::QUANTITY => 0,
                ],
                1,
                StockItemConfigurationInterface::BACKORDERS_YES_NOTIFY,
                false,
                [],
            ]
        ];
    }
}
