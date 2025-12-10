<?php
/**
 * Copyright 2020 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Test\Unit\Model\Queue;

use Magento\InventoryCatalogApi\Model\GetParentSkusOfChildrenSkusInterface;
use Magento\InventoryIndexer\Model\Queue\GetSalabilityDataForUpdate;
use Magento\InventoryIndexer\Model\Queue\ReservationData;
use Magento\InventorySalesApi\Api\AreProductsSalableInterface;
use Magento\InventorySalesApi\Api\Data\IsProductSalableResultInterface;
use Magento\InventorySalesApi\Model\GetStockItemsDataInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[
    CoversClass(GetSalabilityDataForUpdate::class),
]
class GetSalabilityDataForUpdateTest extends TestCase
{
    /**
     * @var GetParentSkusOfChildrenSkusInterface|MockObject
     */
    private $getParentSkusOfChildrenSkus;
    /**
     * @var AreProductsSalableInterface|MockObject
     */
    private $areProductsSalable;
    /**
     * @var GetStockItemsDataInterface|MockObject
     */
    private $getStockItemsData;
    /**
     * @var GetSalabilityDataForUpdate
     */
    private $model;

    /**
     * @var array
     */
    private $salability = [
        'P1' => true,
        'P2' => true,
        'P21' => true,
        'P22' => true,
    ];

    /**
     * @var array
     */
    private $actualSalability = [
        'P1' => false,
        'P2' => true,
        'P21' => false,
        'P22' => true,
    ];

    /**
     * @inheridoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->getParentSkusOfChildrenSkus = $this->createMock(GetParentSkusOfChildrenSkusInterface::class);
        $this->areProductsSalable = $this->createMock(AreProductsSalableInterface::class);
        $this->getStockItemsData = $this->createMock(GetStockItemsDataInterface::class);
        $this->model = new GetSalabilityDataForUpdate(
            $this->getParentSkusOfChildrenSkus,
            $this->areProductsSalable,
            $this->getStockItemsData,
        );
    }

    /**
     * @param array $skus
     * @param array $parentSkus
     * @param array $result
     * @dataProvider executeDataProvider
     */
    public function testExecute(
        array $skus,
        array $parentSkus,
        array $result
    ): void {
        $stockId = 1;

        $this->getParentSkusOfChildrenSkus->expects(self::once())
            ->method('execute')
            ->with($skus)
            ->willReturn($parentSkus);
        $this->areProductsSalable->expects(self::once())
            ->method('execute')
            ->willReturnCallback(
                function ($skus, $stockId) {
                    $result = [];
                    foreach ($skus as $sku) {
                        $isSalable = $this->actualSalability[$sku] ?? false;
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
        $this->getStockItemsData->expects(self::once())
            ->method('execute')
            ->willReturnCallback(
                function ($skus) {
                    $result = [];
                    foreach ($skus as $sku) {
                        $result[$sku] = ['is_salable' => $this->salability[$sku] ?? false];
                    }
                    return $result;
                }
            );

        $reservation = new ReservationData($skus, $stockId);
        $this->assertEquals($result, $this->model->execute($reservation));
    }

    /**
     * @return array
     */
    public static function executeDataProvider(): array
    {
        return [
            [
                [],
                [],
                []
            ],
            [
                ['P1', 'P2'],
                ['P1' => [], 'P2' => ['P21', 'P22']],
                ['P1' => false, 'P21' => false]
            ],
            [
                ['P3', 'P2'],
                ['P3' => [], 'P2' => ['P22', 'P23']],
                []
            ]
        ];
    }
}
