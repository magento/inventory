<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Test\Unit\Model\Queue;

use Magento\InventoryIndexer\Model\Queue\GetSalabilityDataForUpdate;
use Magento\InventoryIndexer\Model\Queue\ReservationData;
use Magento\InventorySalesApi\Api\AreProductsSalableInterface;
use Magento\InventorySalesApi\Api\Data\IsProductSalableResultInterface;
use Magento\InventorySalesApi\Model\GetStockItemDataInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for GetSalabilityDataForUpdate
 */
class GetSalabilityDataForUpdateTest extends TestCase
{
    /**
     * @var AreProductsSalableInterface|MockObject
     */
    private $areProductsSalable;
    /**
     * @var GetStockItemDataInterface|MockObject
     */
    private $getStockItemData;
    /**
     * @var GetSalabilityDataForUpdate
     */
    private $model;

    /**
     * @var array
     */
    private $salability = [
        'P1' => true,
        'P2' => true
    ];

    /**
     * @var array
     */
    private $actualSalability = [
        'P1' => false,
        'P2' => true
    ];

    /**
     * @inheridoc
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->areProductsSalable = $this->createMock(AreProductsSalableInterface::class);
        $this->getStockItemData = $this->createMock(GetStockItemDataInterface::class);
        $this->model = new GetSalabilityDataForUpdate(
            $this->areProductsSalable,
            $this->getStockItemData
        );
        $this->areProductsSalable->method('execute')
            ->willReturnCallback(
                function ($skus, $stockId) {
                    $result = [];
                    foreach ($this->actualSalability as $sku => $isSalable) {
                        if (!in_array($sku, $skus)) {
                            continue;
                        }
                        $result[] = $this->createConfiguredMock(
                            IsProductSalableResultInterface::class,
                            [
                                'getSku' => $sku,
                                'getStockId' => $stockId,
                                'isSalable' => $isSalable,
                            ]
                        );
                    }
                    return $result;
                }
            );
        $this->getStockItemData->method('execute')
            ->willReturnCallback(
                function ($sku) {
                    return isset($this->salability[$sku])
                        ? ['is_salable' => $this->salability[$sku]]
                        : null;
                }
            );
    }

    /**
     * @param array $skus
     * @param array $result
     * @dataProvider executeDataProvider
     */
    public function testExecute(
        array $skus,
        array $result
    ): void {
        $stockId = 1;
        $reservation = new ReservationData($skus, $stockId);
        $this->assertEquals($result, $this->model->execute($reservation));
    }

    /**
     * @return array
     */
    public function executeDataProvider(): array
    {
        return [
            [
                [],
                []
            ],
            [
                ['P1', 'P2'],
                ['P1' => false]
            ],
            [
                ['P3', 'P2'],
                []
            ]
        ];
    }
}
