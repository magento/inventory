<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Test\Unit\Model\ResourceModel\IsStockItemSalableCondition;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\InventorySales\Model\ResourceModel\IsStockItemSalableCondition\GetIsStockItemSalableConditionInterface;
use Magento\InventorySales\Model\ResourceModel\IsStockItemSalableCondition\IsAnySourceItemInStockCondition;
use Magento\InventorySales\Model\ResourceModel\IsStockItemSalableCondition\IsStockItemSalableConditionChain;
use PHPUnit\Framework\TestCase;

/**
 * Test for IsStockItemSalableConditionChain
 */
class IsStockItemSalableConditionChainTest extends TestCase
{
    /**
     * @param array $conditions
     * @param string $result
     * @dataProvider executeDataProvider
     */
    public function testExecute(array $conditions, string $result)
    {
        $conditionsArr = [];
        $resourceConnection = $this->createMock(ResourceConnection::class);
        $connection = $this->getMockBuilder(\Magento\Framework\DB\Adapter\Pdo\Mysql::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $resourceConnection->method('getConnection')
            ->willReturn($connection);
        $select = $this->createMock(Select::class);
        foreach ($conditions as $condition => $required) {
            $conditionsArr[] = $this->createConfiguredMock(
                $required ? IsAnySourceItemInStockCondition::class : GetIsStockItemSalableConditionInterface::class,
                [
                    'execute' => $condition
                ]
            );
        }
        $model = new IsStockItemSalableConditionChain(
            $resourceConnection,
            $conditionsArr
        );
        $this->assertEquals($result, $model->execute($select));
    }

    /**
     * @return array
     */
    public function executeDataProvider(): array
    {
        return [
            [
                [],
                '1'
            ],
            [
                [
                    'a >= 0' => false
                ],
                'IF((a >= 0), 1, 0)'
            ],
            [
                [
                    'a >= 0' => false,
                    'b = 0 OR c = 1' => false,
                ],
                'IF((a >= 0) OR (b = 0 OR c = 1), 1, 0)'
            ],
            [
                [
                    'e = 1' => true,
                    'a >= 0' => false
                ],
                'IF((e = 1) AND ((a >= 0)), 1, 0)'
            ],
            [
                [
                    'e = 1' => true,
                    'a >= 0' => false,
                    'b = 0 OR c = 1' => false,
                ],
                'IF((e = 1) AND ((a >= 0) OR (b = 0 OR c = 1)), 1, 0)'
            ],
            [
                [
                    'a >= 0' => true
                ],
                'IF(a >= 0, 1, 0)'
            ],
            [
                [
                    'a >= 0' => true,
                    'b = 0 OR c = 1' => true,
                ],
                'IF((a >= 0) AND (b = 0 OR c = 1), 1, 0)'
            ],
        ];
    }
}
