<?php
/**
 * Copyright 2020 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Test\Unit\Model\Queue;

use Magento\InventoryCatalogApi\Model\GetParentSkusOfChildrenSkusInterface;
use Magento\InventoryCatalogApi\Model\GetProductTypesBySkusInterface;
use Magento\InventoryIndexer\Model\Queue\GetSalabilityDataForUpdate;
use Magento\InventoryIndexer\Model\Queue\ReservationData;
use Magento\InventorySalesApi\Api\AreProductsSalableInterface;
use Magento\InventorySalesApi\Api\Data\IsProductSalableResultInterface;
use Magento\InventorySalesApi\Model\GetStockItemsDataInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
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
     * @var GetProductTypesBySkusInterface|MockObject
     */
    private $getProductTypesBySkus;
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
        $this->getProductTypesBySkus = $this->createMock(GetProductTypesBySkusInterface::class);
        $this->areProductsSalable = $this->createMock(AreProductsSalableInterface::class);
        $this->getStockItemsData = $this->createMock(GetStockItemsDataInterface::class);
        $this->model = new GetSalabilityDataForUpdate(
            $this->getParentSkusOfChildrenSkus,
            $this->areProductsSalable,
            $this->getStockItemsData,
            $this->getProductTypesBySkus,
        );
    }

    /**
     * @param array $skus
     * @param array $parentSkus
     * @param array $result
     */
    #[DataProvider('executeDataProvider')]
    public function testExecute(
        array $skus,
        array $parentSkus,
        array $productTypes,
        array $result
    ): void {
        $stockId = 1;
        $existingSkus = array_values(
            array_filter($skus, fn (string $sku): bool => ($productTypes[$sku] ?? null) !== null)
        );
        $expectedSkus = array_values(array_unique(array_merge($existingSkus, ...array_values($parentSkus))));

        if (!$skus) {
            $this->getProductTypesBySkus->expects(self::never())
                ->method('execute');
        } else {
            $this->getProductTypesBySkus->expects(self::once())
                ->method('execute')
                ->with($skus)
                ->willReturn($productTypes);
        }

        if (!$existingSkus) {
            $this->getParentSkusOfChildrenSkus->expects(self::never())
                ->method('execute');
            $this->areProductsSalable->expects(self::never())
                ->method('execute');
            $this->getStockItemsData->expects(self::never())
                ->method('execute');

            $reservation = new ReservationData($skus, $stockId);
            $this->assertEquals($result, $this->model->execute($reservation));

            return;
        }

        $this->getParentSkusOfChildrenSkus->expects(self::once())
            ->method('execute')
            ->with($existingSkus)
            ->willReturn($parentSkus);
        $this->areProductsSalable->expects(self::once())
            ->method('execute')
            ->with($expectedSkus, $stockId)
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
            ->with($expectedSkus, $stockId)
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
                [],
                []
            ],
            [
                ['P1', 'P2'],
                ['P1' => [], 'P2' => ['P21', 'P22']],
                ['P1' => 'simple', 'P2' => 'simple'],
                ['P1' => false, 'P21' => false]
            ],
            [
                ['P3', 'P2'],
                ['P3' => [], 'P2' => ['P22', 'P23']],
                ['P3' => 'simple', 'P2' => 'simple'],
                []
            ],
            [
                ['P1', 'MISSING'],
                ['P1' => []],
                ['P1' => 'simple', 'MISSING' => null],
                ['P1' => false]
            ],
        ];
    }

    public function testExecuteWithMissingCurrentStatusTreatsItAsNotSalable(): void
    {
        $stockId = 1;
        $skus = ['P1'];

        $this->getProductTypesBySkus->expects(self::once())
            ->method('execute')
            ->with($skus)
            ->willReturn(['P1' => 'simple']);
        $this->getParentSkusOfChildrenSkus->expects(self::once())
            ->method('execute')
            ->with($skus)
            ->willReturn(['P1' => []]);
        $this->areProductsSalable->expects(self::once())
            ->method('execute')
            ->with($skus, $stockId)
            ->willReturn([
                $this->createConfiguredMock(
                    IsProductSalableResultInterface::class,
                    [
                        'getSku' => 'P1',
                        'getStockId' => $stockId,
                        'isSalable' => true,
                    ]
                ),
            ]);
        $this->getStockItemsData->expects(self::once())
            ->method('execute')
            ->with($skus, $stockId)
            ->willReturn([]);

        $reservation = new ReservationData($skus, $stockId);
        $this->assertEquals(['P1' => true], $this->model->execute($reservation));
    }
}
