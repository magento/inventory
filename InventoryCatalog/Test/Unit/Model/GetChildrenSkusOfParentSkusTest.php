<?php
/**
 * Copyright 2025 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Test\Unit\Model;

use Magento\Catalog\Model\ResourceModel\Product\Relation;
use Magento\InventoryCatalog\Model\GetChildrenSkusOfParentSkus;
use Magento\InventoryCatalogApi\Model\GetProductIdsBySkusInterface;
use Magento\InventoryCatalogApi\Model\GetSkusByProductIdsInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GetChildrenSkusOfParentSkusTest extends TestCase
{
    /**
     * @var Relation|MockObject
     */
    private $productRelationResourceMock;

    /**
     * @var GetProductIdsBySkusInterface|MockObject
     */
    private $getProductIdsBySkusMock;

    /**
     * @var GetSkusByProductIdsInterface|MockObject
     */
    private $getSkusByProductIdsMock;

    /**
     * @var GetChildrenSkusOfParentSkus
     */
    private $model;

    protected function setUp(): void
    {
        $this->productRelationResourceMock = $this->createMock(Relation::class);
        $this->getProductIdsBySkusMock = $this->createMock(GetProductIdsBySkusInterface::class);
        $this->getSkusByProductIdsMock = $this->createMock(GetSkusByProductIdsInterface::class);
        $this->model = new GetChildrenSkusOfParentSkus(
            $this->productRelationResourceMock,
            $this->getProductIdsBySkusMock,
            $this->getSkusByProductIdsMock,
        );
    }

    public function testExecuteNoSkus(): void
    {
        $this->getProductIdsBySkusMock->expects(self::never())->method('execute');
        $this->productRelationResourceMock->expects(self::never())->method('getRelationsByParent');
        $this->model->execute([]);
    }

    public function testExecute(): void
    {
        $childrenSkusOfParentSkus = [
            'configurable1' => ['simple-1'],
            'grouped1' => [],
            'bundle1' => ['simple-1', 'simple-2'],
        ];

        $this->getProductIdsBySkusMock->expects(self::once())->method('execute')
            ->with(array_keys($childrenSkusOfParentSkus))
            ->willReturn(['configurable1' => 10, 'grouped1' => 20, 'bundle1' => 30]);
        $this->productRelationResourceMock->expects(self::once())->method('getRelationsByParent')
            ->with([10, 20, 30])
            ->willReturn([10 => [2], 20 => [], 30 => [2, 3]]);
        $this->getSkusByProductIdsMock->expects(self::once())->method('execute')
            ->with(self::equalToCanonicalizing([2, 3]))
            ->willReturn([2 => 'simple-1', 3 => 'simple-2']);

        $result = $this->model->execute(array_keys($childrenSkusOfParentSkus));
        self::assertEquals($childrenSkusOfParentSkus, $result);
    }
}
