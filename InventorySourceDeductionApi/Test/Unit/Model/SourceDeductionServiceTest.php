<?php
/**
 * Copyright 2024 Adobe
 * All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySourceDeductionApi\Test\Unit\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Inventory\Model\SourceItem\Command\DecrementSourceItemQty;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryConfigurationApi\Api\Data\StockItemConfigurationExtensionInterface;
use Magento\InventoryConfigurationApi\Api\Data\StockItemConfigurationInterface;
use Magento\InventoryConfigurationApi\Api\GetStockItemConfigurationInterface;
use Magento\InventoryConfigurationApi\Model\InventoryConfigurationInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\GetStockBySalesChannelInterface;
use Magento\InventorySourceDeductionApi\Model\GetSourceItemBySourceCodeAndSku;
use Magento\InventorySourceDeductionApi\Model\ItemToDeductInterface;
use Magento\InventorySourceDeductionApi\Model\SourceDeductionRequestInterface;
use Magento\InventorySourceDeductionApi\Model\SourceDeductionService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for source deduction service
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @see SourceDeductionService
 */
class SourceDeductionServiceTest extends TestCase
{
    /**
     * @var GetSourceItemBySourceCodeAndSku|(GetSourceItemBySourceCodeAndSku&MockObject)|MockObject
     */
    private $getSourceItemBySourceCodeAndSkuMock;
    /**
     * @var GetStockItemConfigurationInterface|(GetStockItemConfigurationInterface&MockObject)|MockObject
     */
    private $getStockItemConfigurationMock;
    /**
     * @var GetStockBySalesChannelInterface|(GetStockBySalesChannelInterface&MockObject)|MockObject
     */
    private $getStockBySalesChannelMock;
    /**
     * @var DecrementSourceItemQty|(DecrementSourceItemQty&MockObject)|MockObject
     */
    private $decrementSourceItemMock;
    /**
     * @var InventoryConfigurationInterface|(InventoryConfigurationInterface&MockObject)|MockObject
     */
    private $inventoryConfigurationMock;

    /**
     * @var SourceDeductionService
     */
    private $model;

    protected function setUp(): void
    {
        $this->getSourceItemBySourceCodeAndSkuMock = $this->createMock(GetSourceItemBySourceCodeAndSku::class);
        $this->getStockItemConfigurationMock = $this->createMock(GetStockItemConfigurationInterface::class);
        $this->getStockBySalesChannelMock = $this->createMock(GetStockBySalesChannelInterface::class);
        $this->decrementSourceItemMock = $this->createMock(DecrementSourceItemQty::class);
        $this->inventoryConfigurationMock = $this->createMock(InventoryConfigurationInterface::class);

        $this->model = new SourceDeductionService(
            $this->getSourceItemBySourceCodeAndSkuMock,
            $this->getStockItemConfigurationMock,
            $this->getStockBySalesChannelMock,
            $this->decrementSourceItemMock,
            $this->inventoryConfigurationMock,
        );
    }

    /**
     * @param array $itemsData
     * @param bool $isCanBackInStock
     * @return void
     * @throws LocalizedException
     *
     * @dataProvider executeDataProvider
     */
    public function testExecute(array $itemsData, bool $isCanBackInStock): void
    {
        $sourceCode = 'test_source_code';
        $items = [];
        $itemConfigurations = [];
        $sourceItems = [];
        $testcase = $this;
        foreach ($itemsData as $itemData) {
            $item = $this->createMock(ItemToDeductInterface::class);
            $item->method('getSku')->willReturn($itemData['sku']);
            $item->method('getQty')->willReturn($itemData['qty']);
            $items[] = $item;

            $itemConfigurationData = $itemData['stockItemConfigurationData'];
            $itemConfiguration = $this->getMockForAbstractClass(StockItemConfigurationInterface::class);
            $itemConfiguration->method('isManageStock')->willReturn($itemConfigurationData['isManageStock']);
            $itemConfiguration->method('getMinQty')->willReturn($itemConfigurationData['getMinQty']);
            $itemConfiguration->method('getBackorders')->willReturn($itemConfigurationData['getBackorders']);

            $extensionAttributes = $this->getMockBuilder(StockItemConfigurationExtensionInterface::class)
                ->disableOriginalConstructor()
                ->addMethods(['getIsInStock'])
                ->getMockForAbstractClass();
            $extensionAttributes->method('getIsInStock')->willReturn($itemConfigurationData['isInStock']);
            $itemConfiguration->method('getExtensionAttributes')->willReturn($extensionAttributes);
            $itemConfigurations[$itemData['sku']] = $itemConfiguration;

            $sourceItem = $this->createMock(SourceItemInterface::class);
            $sourceItem->method('getQuantity')
                ->willReturnOnConsecutiveCalls(
                    $itemData['sourceItemData']['qty'],
                    $itemData['sourceItemData']['qty'],
                    $itemData['finalQty']
                );
            $sourceItem
                ->method('setQuantity')
                ->willReturnCallback(
                    function ($newQty) use ($testcase, $itemData) {
                        $testcase->assertEquals(
                            $itemData['finalQty'],
                            $newQty,
                            'Final qty is wrong for ' . $itemData['sku']
                        );
                    }
                );
            $sourceItem
                ->method('setStatus')
                ->willReturnCallback(
                    function ($newStockStatus) use ($testcase, $itemData) {
                        $testcase->assertEquals(
                            $itemData['finalStockStatus'],
                            $newStockStatus,
                            'Stock status is wrong for ' . $itemData['sku']
                        );
                    }
                );
            $sourceItems[$itemData['sku']] = $sourceItem;
        }
        $sourceDeductionRequest = $this->createMock(SourceDeductionRequestInterface::class);
        $salesChannel = $this->createMock(SalesChannelInterface::class);
        $sourceDeductionRequest->expects($this->once())->method('getSalesChannel')->willReturn($salesChannel);
        $sourceDeductionRequest->expects($this->once())->method('getSourceCode')->willReturn($sourceCode);
        $sourceDeductionRequest->expects($this->once())->method('getItems')->willReturn($items);

        $stock = $this->createMock(\Magento\InventoryApi\Api\Data\StockInterface::class);
        $stock->method('getStockId')->willReturn(1);
        $this->getStockBySalesChannelMock->method('execute')->willReturn($stock);
        $this->getStockItemConfigurationMock->method('execute')->willReturnCallback(
            function ($sku) use ($itemConfigurations) {
                return $itemConfigurations[$sku];
            }
        );
        $this->getSourceItemBySourceCodeAndSkuMock->method('execute')->willReturnCallback(
            function ($sourceCode, $itemSku) use ($sourceItems) {
                return $sourceItems[$itemSku];
            }
        );

        $this->inventoryConfigurationMock->method('isCanBackInStock')->willReturn($isCanBackInStock);
        $this->model->execute($sourceDeductionRequest);
    }

    /**
     * @return array[]
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public static function executeDataProvider(): array
    {
        return [
            'Test Can back in stock' => [
                'itemsData' => [
                    [
                        'sku' => 'SKU-1',
                        'qty' => -4.00,
                        'finalQty' => 4.00,
                        'finalStockStatus' => 1,
                        'stockItemConfigurationData' => [
                            'isManageStock' => true,
                            'isInStock' => false,
                            'getMinQty' => 0.00,
                            'getBackorders' => 0,
                        ],
                        'sourceItemData' => [
                            'qty' => 0.00,
                        ]
                    ],
                    [
                        'sku' => 'SKU-2',
                        'qty' => -2.00,
                        'finalQty' => 2.00,
                        'finalStockStatus' => 0,
                        'stockItemConfigurationData' => [
                            'isManageStock' => true,
                            'isInStock' => false,
                            'getMinQty' => 2.00,
                            'getBackorders' => 0,
                        ],
                        'sourceItemData' => [
                            'qty' => 0.00,
                        ]
                    ],
                    [
                        'sku' => 'SKU-3',
                        'qty' => -2.00,
                        'finalQty' => 2.00,
                        'finalStockStatus' => 0,
                        'stockItemConfigurationData' => [
                            'isManageStock' => true,
                            'isInStock' => false,
                            'getMinQty' => 3.00,
                            'getBackorders' => 0,
                        ],
                        'sourceItemData' => [
                            'qty' => 0.00,
                        ]
                    ],
                    [
                        'sku' => 'SKU-4',
                        'qty' => -2.00,
                        'finalQty' => 3.00,
                        'finalStockStatus' => 1,
                        'stockItemConfigurationData' => [
                            'isManageStock' => true,
                            'isInStock' => false,
                            'getMinQty' => 2.00,
                            'getBackorders' => 1,
                        ],
                        'sourceItemData' => [
                            'qty' => 1.00,
                        ]
                    ],
                    [
                        'sku' => 'SKU-5',
                        'qty' => 2.00,
                        'finalQty' => 0.00,
                        'finalStockStatus' => 0,
                        'stockItemConfigurationData' => [
                            'isManageStock' => true,
                            'isInStock' => true,
                            'getMinQty' => 0.00,
                            'getBackorders' => 0,
                        ],
                        'sourceItemData' => [
                            'qty' => 2.00,
                        ]
                    ],
                ],
                'isCanBackInStock' => true,
            ],
            'Test NO back in stock' => [
                'itemsData' => [
                    [
                        'sku' => 'SKU-1',
                        'qty' => -4.00,
                        'finalQty' => 4.00,
                        'finalStockStatus' => 0,
                        'stockItemConfigurationData' => [
                            'isManageStock' => true,
                            'isInStock' => false,
                            'getMinQty' => 0.00,
                            'getBackorders' => 0,
                        ],
                        'sourceItemData' => [
                            'qty' => 0.00,
                        ]
                    ],
                    [
                        'sku' => 'SKU-4',
                        'qty' => -2.00,
                        'finalQty' => 3.00,
                        'finalStockStatus' => 0,
                        'stockItemConfigurationData' => [
                            'isManageStock' => true,
                            'isInStock' => false,
                            'getMinQty' => 2.00,
                            'getBackorders' => 1,
                        ],
                        'sourceItemData' => [
                            'qty' => 1.00,
                        ]
                    ],
                    [
                        'sku' => 'SKU-2',
                        'qty' => 2.00,
                        'finalQty' => 2.00,
                        'finalStockStatus' => 0,
                        'stockItemConfigurationData' => [
                            'isManageStock' => true,
                            'isInStock' => false,
                            'getMinQty' => 2.00,
                            'getBackorders' => 1,
                        ],
                        'sourceItemData' => [
                            'qty' => 4.00,
                        ]
                    ],
                ],
                'isCanBackInStock' => false,
            ],
            //
            'Turn out of stock to in stock after deducting' => [
                'itemsData' => [
                    [
                        'sku' => 'SKU-2',
                        'qty' => 2.00,
                        'finalQty' => 2.00,
                        'finalStockStatus' => 1,
                        'stockItemConfigurationData' => [
                            'isManageStock' => true,
                            'isInStock' => false,
                            'getMinQty' => 0.00,
                            'getBackorders' => 0,
                        ],
                        'sourceItemData' => [
                            'qty' => 4.00,
                        ]
                    ],
                ],
                'isCanBackInStock' => true,
            ],
        ];
    }
}
