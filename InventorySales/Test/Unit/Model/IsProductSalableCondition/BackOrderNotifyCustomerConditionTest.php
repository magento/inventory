<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Test\Unit\Model\IsProductSalableCondition;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\InventoryConfigurationApi\Api\Data\StockItemConfigurationInterface;
use Magento\InventoryConfigurationApi\Api\GetStockItemConfigurationInterface;
use Magento\InventorySales\Model\GetBackorderQty;
use Magento\InventorySales\Model\IsProductSalableCondition\BackOrderNotifyCustomerCondition;
use Magento\InventorySalesApi\Api\Data\ProductSalabilityErrorInterface;
use Magento\InventorySalesApi\Api\Data\ProductSalabilityErrorInterfaceFactory;
use Magento\InventorySalesApi\Api\Data\ProductSalableResultInterface;
use Magento\InventorySalesApi\Api\Data\ProductSalableResultInterfaceFactory;
use Magento\InventorySalesApi\Api\GetProductSalableQtyInterface;
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
     * @var GetProductSalableQtyInterface|MockObject
     */
    private $getProductSalableQty;

    /**
     * @var GetBackorderQty|MockObject
     */
    private $getBackorderQty;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();
        $objectManager = new ObjectManager($this);
        $productSalableResultFactory = $this->createMock(
            ProductSalableResultInterfaceFactory::class
        );
        $this->getProductSalableQty = $this->createMock(
            GetProductSalableQtyInterface::class
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
                    $mock->method('getCode')->willReturn($args['code']);
                    $mock->method('getMessage')->willReturn($args['message']->render());
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
        $this->getBackorderQty = $this->createMock(
            GetBackorderQty::class
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
                'getProductSalableQty' => $this->getProductSalableQty,
                'getBackorderQty' => $this->getBackorderQty
            ]
        );
    }

    /**
     * Test execute with different stock settings
     *
     * @dataProvider executeDataProvider
     * @param array|null $stockData
     * @param int $reqQty
     * @param float $salableQty
     * @param int $backOrders
     * @param int $backorderQty
     * @param bool $manageStock
     * @param array $errors
     * @throws LocalizedException
     */
    public function testExecute(
        ?array $stockData,
        int $reqQty,
        float $salableQty,
        int $backOrders,
        float $backorderQty,
        bool $manageStock,
        array $errors
    ): void {
        $this->stockItemConfiguration->method('isManageStock')
            ->willReturn($manageStock);
        $this->stockItemConfiguration->method('getBackorders')
            ->willReturn($backOrders);
        $this->getBackorderQty->method('execute')
            ->willReturn((float)$backorderQty);
        $this->getProductSalableQty->method('execute')
            ->willReturn((float)$salableQty);
        $this->stockItemData = $stockData;
        $actualErrors = [];
        foreach ($this->model->execute('simple', 1, $reqQty)->getErrors() as $error) {
            $actualErrors[] = ['code' => $error->getCode(), 'message' => $error->getMessage()];
        }
        $this->assertEquals($errors, $actualErrors);
    }

    /**
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function executeDataProvider(): array
    {
        return [
            'StockQty=1, ReqQty=2, SalableQty=1, Backorders=YesNotify, ManageStock=Yes' => [
                [
                    GetStockItemDataInterface::QUANTITY => 1,
                ],
                2,
                1,
                StockItemConfigurationInterface::BACKORDERS_YES_NOTIFY,
                1,
                true,
                [
                    [
                        'code' => 'back_order-not-enough',
                        'message' => 'We don\'t have as many quantity as you requested,'
                            . ' but we\'ll back order the remaining 1.'
                    ]
                ],
            ],
            'StockQty=10, ReqQty=20, SalableQty=0, Backorders=YesNotify, ManageStock=Yes' => [
                [
                    GetStockItemDataInterface::QUANTITY => 10,
                ],
                20,
                0,
                StockItemConfigurationInterface::BACKORDERS_YES_NOTIFY,
                20,
                true,
                [
                    [
                        'code' => 'back_order-not-enough',
                        'message' => 'We don\'t have as many quantity as you requested,'
                            . ' but we\'ll back order the remaining 20.'
                    ]
                ],
            ],
            'StockQty=10, ReqQty=20, SalableQty=15, Backorders=YesNotify, ManageStock=Yes' => [
                [
                    GetStockItemDataInterface::QUANTITY => 10,
                ],
                20,
                15,
                StockItemConfigurationInterface::BACKORDERS_YES_NOTIFY,
                5,
                true,
                [
                    [
                        'code' => 'back_order-not-enough',
                        'message' => 'We don\'t have as many quantity as you requested,'
                            . ' but we\'ll back order the remaining 5.'
                    ]
                ],
            ],
            'StockQty=1, ReqQty=1, SalableQty=1, Backorders=YesNotify, ManageStock=Yes' => [
                [
                    GetStockItemDataInterface::QUANTITY => 1,
                ],
                1,
                1,
                StockItemConfigurationInterface::BACKORDERS_YES_NOTIFY,
                0,
                true,
                [],
            ],
            'StockQty=?, ReqQty=1, SalableQty=1, Backorders=YesNotify, ManageStock=Yes' => [
                null,
                1,
                1,
                StockItemConfigurationInterface::BACKORDERS_YES_NOTIFY,
                0,
                true,
                [],
            ],
            'StockQty=0, ReqQty=1, SalableQty=1, Backorders=No, ManageStock=Yes' => [
                [
                    GetStockItemDataInterface::QUANTITY => 0,
                ],
                1,
                1,
                StockItemConfigurationInterface::BACKORDERS_NO,
                0,
                true,
                [],
            ],
            'StockQty=0, ReqQty=1, SalableQty=1, Backorders=Yes, ManageStock=Yes' => [
                [
                    GetStockItemDataInterface::QUANTITY => 0,
                ],
                1,
                1,
                StockItemConfigurationInterface::BACKORDERS_YES_NONOTIFY,
                0,
                true,
                [],
            ],
            'StockQty=0, ReqQty=1, SalableQty=1, Backorders=YesNotify, ManageStock=No' => [
                [
                    GetStockItemDataInterface::QUANTITY => 0,
                ],
                1,
                1,
                StockItemConfigurationInterface::BACKORDERS_YES_NOTIFY,
                0,
                false,
                [],
            ],
            'StockQty=3, ReqQty=5, SalableQty=13, Backorders=YesNotify, ManageStock=Yes' => [
                [
                    GetStockItemDataInterface::QUANTITY => 3,
                ],
                5,
                13,
                StockItemConfigurationInterface::BACKORDERS_YES_NOTIFY,
                8,
                true,
                [
                    [
                        'code' => 'back_order-not-enough',
                        'message' => 'We don\'t have as many quantity as you requested,'
                            . ' but we\'ll back order the remaining 8.'
                    ]
                ],
            ],
            'StockQty=3, ReqQty=3, SalableQty=13, Backorders=YesNotify, ManageStock=Yes' => [
                [
                    GetStockItemDataInterface::QUANTITY => 3,
                ],
                3,
                13,
                StockItemConfigurationInterface::BACKORDERS_YES_NOTIFY,
                -10,
                true,
                [],
            ]
        ];
    }
}
