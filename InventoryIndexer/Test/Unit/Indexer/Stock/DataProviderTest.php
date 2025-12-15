<?php
/**
 * Copyright 2025 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Test\Unit\Indexer\Stock;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\InventoryIndexer\Indexer\SelectBuilder;
use Magento\InventoryIndexer\Indexer\SiblingSelectBuilderInterface;
use Magento\InventoryIndexer\Indexer\Stock\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DataProviderTest extends TestCase
{
    /**
     * @var SelectBuilder|MockObject
     */
    private $selectBuilderMock;

    /**
     * @var SiblingSelectBuilderInterface|MockObject
     */
    private $bundleSelectBuilderMock;

    /**
     * @var SiblingSelectBuilderInterface|MockObject
     */
    private $groupedSelectBuilderMock;

    /**
     * @var AdapterInterface|MockObject
     */
    private $connectionMock;

    /**
     * @var DataProvider
     */
    private $dataProvider;

    protected function setUp(): void
    {
        $resourceConnectionMock = $this->createMock(ResourceConnection::class);
        $this->selectBuilderMock = $this->createMock(SelectBuilder::class);
        $this->bundleSelectBuilderMock = $this->createMock(SiblingSelectBuilderInterface::class);
        $this->groupedSelectBuilderMock = $this->createMock(SiblingSelectBuilderInterface::class);

        $this->connectionMock = $this->createMock(AdapterInterface::class);
        $resourceConnectionMock->method('getConnection')->willReturn($this->connectionMock);

        $this->dataProvider = new DataProvider(
            $resourceConnectionMock,
            $this->selectBuilderMock,
            ['bundle' => $this->bundleSelectBuilderMock, 'grouped' => $this->groupedSelectBuilderMock],
        );
    }

    public function testGetData(): void
    {
        $stockId = 2;
        $skuList = ['sku1', 'sku2'];
        $data = [
            ['sku' => 'sku1', 'quantity' => 10, 'is_salable' => 1],
            ['sku' => 'sku2', 'quantity' => 0, 'is_salable' => 0],
        ];

        $selectMock = $this->createMock(Select::class);
        $this->selectBuilderMock->expects(self::once())->method('getSelect')->with($stockId)->willReturn($selectMock);
        $this->connectionMock->expects(self::once())->method('fetchAll')->with($selectMock)->willReturn($data);
        $this->bundleSelectBuilderMock->expects(self::never())->method('getSelect');
        $this->groupedSelectBuilderMock->expects(self::never())->method('getSelect');

        $result = $this->dataProvider->getData($stockId, $skuList);
        self::assertEquals(array_combine($skuList, $data), $result);
    }

    public function testGetSiblingsData(): void
    {
        $stockId = 2;
        $skuList = ['bundle1', 'bundle2'];
        $data = [
            ['sku' => 'bundle1', 'quantity' => 10, 'is_salable' => 1],
            ['sku' => 'bundle2', 'quantity' => 0, 'is_salable' => 0],
        ];

        $selectMock = $this->createMock(Select::class);
        $this->bundleSelectBuilderMock->expects(self::once())
            ->method('getSelect')
            ->with($stockId, $skuList)
            ->willReturn($selectMock);
        $this->connectionMock->expects(self::once())->method('fetchAll')->with($selectMock)->willReturn($data);
        $this->selectBuilderMock->expects(self::never())->method('getSelect');
        $this->groupedSelectBuilderMock->expects(self::never())->method('getSelect');

        $result = $this->dataProvider->getSiblingsData($stockId, 'bundle', $skuList);
        self::assertEquals(array_combine($skuList, $data), $result);
    }
}
