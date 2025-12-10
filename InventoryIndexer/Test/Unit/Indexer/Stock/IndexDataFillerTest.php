<?php
/**
 * Copyright 2025 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Test\Unit\Indexer\Stock;

use Magento\InventoryCatalogApi\Model\GetProductTypesBySkusInterface;
use Magento\InventoryConfigurationApi\Model\GetAllowedProductTypesForSourceItemManagementInterface;
use Magento\InventoryIndexer\Indexer\SiblingProductsProvidersPool;
use Magento\InventoryIndexer\Indexer\SourceItem\SkuListInStock;
use Magento\InventoryIndexer\Indexer\Stock\DataProvider;
use Magento\InventoryIndexer\Indexer\Stock\IndexDataFiller;
use Magento\InventoryIndexer\Indexer\Stock\PrepareReservationsIndexData;
use Magento\InventoryIndexer\Indexer\Stock\ReservationsIndexTable;
use Magento\InventoryMultiDimensionalIndexerApi\Model\Alias as IndexAliasModel;
use Magento\InventoryMultiDimensionalIndexerApi\Model\IndexAlias;
use Magento\InventoryMultiDimensionalIndexerApi\Model\IndexHandlerInterface;
use Magento\InventoryMultiDimensionalIndexerApi\Model\IndexName;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
#[
    CoversClass(IndexDataFiller::class),
]
class IndexDataFillerTest extends TestCase
{
    /**
     * @var ReservationsIndexTable|MockObject
     */
    private $reservationsIndexTableMock;

    /**
     * @var PrepareReservationsIndexData|MockObject
     */
    private $prepareReservationsIndexDataMock;

    /**
     * @var GetProductTypesBySkusInterface|MockObject
     */
    private $getProductTypesBySkusMock;

    /**
     * @var DataProvider|MockObject
     */
    private $dataProviderMock;

    /**
     * @var IndexHandlerInterface|MockObject
     */
    private $indexStructureHandlerMock;

    /**
     * @var SiblingProductsProvidersPool|MockObject
     */
    private $siblingProductsProvidersPoolMock;

    /**
     * @var string
     */
    private $connectionName;

    /**
     * @var IndexDataFiller
     */
    private $indexDataFiller;

    protected function setUp(): void
    {
        $this->reservationsIndexTableMock = $this->createMock(ReservationsIndexTable::class);
        $this->prepareReservationsIndexDataMock = $this->createMock(PrepareReservationsIndexData::class);
        $this->getProductTypesBySkusMock = $this->createMock(GetProductTypesBySkusInterface::class);
        $getSourceItemManagementProductTypesMock = $this->createMock(
            GetAllowedProductTypesForSourceItemManagementInterface::class
        );
        $this->dataProviderMock = $this->createMock(DataProvider::class);
        $this->indexStructureHandlerMock = $this->createMock(IndexHandlerInterface::class);
        $this->siblingProductsProvidersPoolMock = $this->createMock(SiblingProductsProvidersPool::class);

        $getSourceItemManagementProductTypesMock->method('execute')->willReturn(['simple']);
        $this->connectionName = 'default';

        $this->indexDataFiller = new IndexDataFiller(
            $this->reservationsIndexTableMock,
            $this->prepareReservationsIndexDataMock,
            $this->getProductTypesBySkusMock,
            $getSourceItemManagementProductTypesMock,
            $this->dataProviderMock,
            $this->indexStructureHandlerMock,
            $this->siblingProductsProvidersPoolMock,
        );
    }

    public function testFillIndexSimpleProducts(): void
    {
        $stockId = 2;
        $simpleSkuList = ['simple-1', 'simple-2'];

        $aliasMock = $this->createMock(IndexAliasModel::class);
        $aliasMock->method('getValue')->willReturn(IndexAlias::MAIN->value);
        $indexNameMock = $this->createMock(IndexName::class);
        $indexNameMock->method('getAlias')->willReturn($aliasMock);
        $skuListInStockMock = $this->createMock(SkuListInStock::class);
        $skuListInStockMock->method('getStockId')->willReturn($stockId);
        $skuListInStockMock->method('getSkuList')->willReturn($simpleSkuList);

        $this->reservationsIndexTableMock->expects(self::once())->method('createTable')->with($stockId);
        $this->prepareReservationsIndexDataMock->expects(self::once())->method('execute')->with($stockId);
        $this->reservationsIndexTableMock->expects(self::once())->method('dropTable')->with($stockId);

        $typesBySkus = [
            'simple-1' => 'simple',
            'simple-2' => 'simple',
        ];
        $this->getProductTypesBySkusMock->expects(self::once())
            ->method('execute')
            ->with($simpleSkuList)
            ->willReturn($typesBySkus);
        $data = [
            'simple-1' => ['sku' => 'simple-1', 'quantity' => 10, 'is_salable' => true],
            'simple-2' => ['sku' => 'simple-2', 'quantity' => 0, 'is_salable' => false],
        ];
        $this->dataProviderMock->expects(self::once())
            ->method('getData')
            ->with($stockId, $simpleSkuList)
            ->willReturn($data);
        $this->indexStructureHandlerMock->expects(self::once())
            ->method('cleanIndex')
            ->with($indexNameMock, new \ArrayIterator($simpleSkuList), $this->connectionName);
        $this->indexStructureHandlerMock->expects(self::once())
            ->method('saveIndex')
            ->with($indexNameMock, new \ArrayIterator($data), $this->connectionName);
        $this->siblingProductsProvidersPoolMock->expects(self::once())
            ->method('getSiblingsGroupedByType')
            ->with($simpleSkuList)
            ->willReturn([]);
        $this->dataProviderMock->expects(self::never())->method('getSiblingsData');

        $this->indexDataFiller->fillIndex($indexNameMock, $skuListInStockMock, $this->connectionName);
    }

    public function testFillIndexSimpleProductsWithParents(): void
    {
        $stockId = 2;
        $simpleSkuList = ['simple-1', 'simple-2'];

        $aliasMock = $this->createMock(IndexAliasModel::class);
        $aliasMock->method('getValue')->willReturn(IndexAlias::MAIN->value);
        $indexNameMock = $this->createMock(IndexName::class);
        $indexNameMock->method('getAlias')->willReturn($aliasMock);
        $skuListInStockMock = $this->createMock(SkuListInStock::class);
        $skuListInStockMock->method('getStockId')->willReturn($stockId);
        $skuListInStockMock->method('getSkuList')->willReturn($simpleSkuList);

        $this->reservationsIndexTableMock->expects(self::once())->method('createTable')->with($stockId);
        $this->prepareReservationsIndexDataMock->expects(self::once())->method('execute')->with($stockId);
        $this->reservationsIndexTableMock->expects(self::once())->method('dropTable')->with($stockId);

        $typesBySkus = [
            'simple-1' => 'simple',
            'simple-2' => 'simple',
        ];
        $this->getProductTypesBySkusMock->expects(self::once())
            ->method('execute')
            ->with($simpleSkuList)
            ->willReturn($typesBySkus);
        $simpleData = [
            'simple-1' => ['sku' => 'simple-1', 'quantity' => 10, 'is_salable' => true],
            'simple-2' => ['sku' => 'simple-2', 'quantity' => 0, 'is_salable' => false],
        ];
        $this->dataProviderMock->expects(self::once())
            ->method('getData')
            ->with($stockId, $simpleSkuList)
            ->willReturn($simpleData);
        $compositeSkuList = ['grouped-1', 'grouped-2'];
        $this->siblingProductsProvidersPoolMock->expects(self::once())
            ->method('getSiblingsGroupedByType')
            ->with($simpleSkuList)
            ->willReturn(['grouped' => $compositeSkuList]);
        $compositeData = [
            'grouped-1' => ['sku' => 'grouped-1', 'quantity' => 15, 'is_salable' => true],
            'grouped-2' => ['sku' => 'grouped-2', 'quantity' => 0, 'is_salable' => false],
        ];
        $this->dataProviderMock->expects(self::once())
            ->method('getSiblingsData')
            ->with($stockId, 'grouped', $compositeSkuList, IndexAlias::MAIN)
            ->willReturn($compositeData);

        $this->indexStructureHandlerMock->expects(self::exactly(2))
            ->method('cleanIndex')
            ->willReturnCallback(
                function (...$args) use ($indexNameMock, $simpleSkuList, $compositeSkuList) {
                    static $callIndex = 0;
                    $callIndex++;
                    self::assertEquals($indexNameMock, $args[0]);
                    self::assertEquals($this->connectionName, $args[2]);
                    if ($callIndex === 1) {
                        self::assertEquals(new \ArrayIterator($simpleSkuList), $args[1]);
                    } elseif ($callIndex === 2) {
                        self::assertEquals(new \ArrayIterator($compositeSkuList), $args[1]);
                    }
                }
            );
        $this->indexStructureHandlerMock->expects(self::exactly(2))
            ->method('saveIndex')
            ->willReturnCallback(
                function (...$args) use ($indexNameMock, $simpleData, $compositeData) {
                    static $callIndex = 0;
                    $callIndex++;
                    self::assertEquals($indexNameMock, $args[0]);
                    self::assertEquals($this->connectionName, $args[2]);
                    if ($callIndex === 1) {
                        self::assertEquals(new \ArrayIterator($simpleData), $args[1]);
                    } elseif ($callIndex === 2) {
                        self::assertEquals(new \ArrayIterator($compositeData), $args[1]);
                    }
                }
            );

        $this->indexDataFiller->fillIndex($indexNameMock, $skuListInStockMock, $this->connectionName);
    }

    public function testFillIndexCompositeProducts(): void
    {
        $stockId = 2;
        $compositeSkuList = ['bundle-1', 'bundle-2'];

        $aliasMock = $this->createMock(IndexAliasModel::class);
        $aliasMock->method('getValue')->willReturn(IndexAlias::MAIN->value);
        $indexNameMock = $this->createMock(IndexName::class);
        $indexNameMock->method('getAlias')->willReturn($aliasMock);
        $skuListInStockMock = $this->createMock(SkuListInStock::class);
        $skuListInStockMock->method('getStockId')->willReturn($stockId);
        $skuListInStockMock->method('getSkuList')->willReturn($compositeSkuList);

        $this->reservationsIndexTableMock->expects(self::once())->method('createTable')->with($stockId);
        $this->prepareReservationsIndexDataMock->expects(self::once())->method('execute')->with($stockId);
        $this->reservationsIndexTableMock->expects(self::once())->method('dropTable')->with($stockId);

        $typesBySkus = [
            'bundle-1' => 'bundle',
            'bundle-2' => 'bundle',
        ];
        $this->getProductTypesBySkusMock->expects(self::once())
            ->method('execute')
            ->with($compositeSkuList)
            ->willReturn($typesBySkus);
        $this->dataProviderMock->expects(self::never())->method('getData');
        $this->siblingProductsProvidersPoolMock->expects(self::once())
            ->method('getSiblingsGroupedByType')
            ->with([])
            ->willReturn([]);
        $data = [
            'bundle-1' => ['sku' => 'bundle-1', 'quantity' => 0, 'is_salable' => false],
            'bundle-2' => ['sku' => 'bundle-2', 'quantity' => 20, 'is_salable' => true],
        ];
        $this->dataProviderMock->expects(self::once())
            ->method('getSiblingsData')
            ->with($stockId, 'bundle', $compositeSkuList, IndexAlias::MAIN)
            ->willReturn($data);
        $this->indexStructureHandlerMock->expects(self::once())
            ->method('cleanIndex')
            ->with($indexNameMock, new \ArrayIterator($compositeSkuList), $this->connectionName);
        $this->indexStructureHandlerMock->expects(self::once())
            ->method('saveIndex')
            ->with($indexNameMock, new \ArrayIterator($data), $this->connectionName);

        $this->indexDataFiller->fillIndex($indexNameMock, $skuListInStockMock, $this->connectionName);
    }

    public function testFillIndexMixedProducts(): void
    {
        $stockId = 2;
        $skuList = ['bundle-1', 'simple-1', 'simple-2', 'grouped-1'];

        $aliasMock = $this->createMock(IndexAliasModel::class);
        $aliasMock->method('getValue')->willReturn(IndexAlias::MAIN->value);
        $indexNameMock = $this->createMock(IndexName::class);
        $indexNameMock->method('getAlias')->willReturn($aliasMock);
        $skuListInStockMock = $this->createMock(SkuListInStock::class);
        $skuListInStockMock->method('getStockId')->willReturn($stockId);
        $skuListInStockMock->method('getSkuList')->willReturn($skuList);

        $this->reservationsIndexTableMock->expects(self::once())->method('createTable')->with($stockId);
        $this->prepareReservationsIndexDataMock->expects(self::once())->method('execute')->with($stockId);
        $this->reservationsIndexTableMock->expects(self::once())->method('dropTable')->with($stockId);

        $typesBySkus = [
            'bundle-1' => 'bundle',
            'simple-1' => 'simple',
            'simple-2' => 'simple',
            'grouped-1' => 'grouped',
        ];
        $this->getProductTypesBySkusMock->expects(self::once())
            ->method('execute')
            ->with($skuList)
            ->willReturn($typesBySkus);
        $simpleData = [
            'simple-1' => ['sku' => 'simple-1', 'quantity' => 10, 'is_salable' => true],
            'simple-2' => ['sku' => 'simple-2', 'quantity' => 0, 'is_salable' => false],
        ];
        $this->dataProviderMock->expects(self::once())
            ->method('getData')
            ->with($stockId, ['simple-1', 'simple-2'])
            ->willReturn($simpleData);
        $this->siblingProductsProvidersPoolMock->expects(self::once())
            ->method('getSiblingsGroupedByType')
            ->with(['simple-1', 'simple-2'])
            ->willReturn(['bundle' => ['bundle-2', 'bundle-3'], 'grouped' => ['grouped-1', 'grouped-2']]);
        $bundleData = [
            'bundle-1' => ['sku' => 'bundle-1', 'quantity' => 15, 'is_salable' => true],
            'bundle-2' => ['sku' => 'bundle-2', 'quantity' => 25, 'is_salable' => true],
            'bundle-3' => ['sku' => 'bundle-3', 'quantity' => 0, 'is_salable' => false],
        ];
        $groupedData = [
            'grouped-1' => ['sku' => 'grouped-1', 'quantity' => 0, 'is_salable' => false],
            'grouped-2' => ['sku' => 'grouped-2', 'quantity' => 30, 'is_salable' => true],
        ];
        $this->dataProviderMock->expects(self::exactly(2))
            ->method('getSiblingsData')
            ->willReturnMap([
                [$stockId, 'bundle', ['bundle-1', 'bundle-2', 'bundle-3'], IndexAlias::MAIN, $bundleData],
                [$stockId, 'grouped', ['grouped-1', 'grouped-2'], IndexAlias::MAIN, $groupedData],
            ]);

        $this->indexStructureHandlerMock->expects(self::exactly(3))
            ->method('cleanIndex')
            ->willReturnCallback(
                function (...$args) use ($indexNameMock) {
                    static $callIndex = 0;
                    $callIndex++;
                    self::assertEquals($indexNameMock, $args[0]);
                    self::assertEquals($this->connectionName, $args[2]);
                    if ($callIndex === 1) {
                        self::assertEquals(new \ArrayIterator(['simple-1', 'simple-2']), $args[1]);
                    } elseif ($callIndex === 2) {
                        self::assertEquals(new \ArrayIterator(['bundle-1', 'bundle-2', 'bundle-3']), $args[1]);
                    } elseif ($callIndex === 3) {
                        self::assertEquals(new \ArrayIterator(['grouped-1', 'grouped-2']), $args[1]);
                    }
                }
            );
        $this->indexStructureHandlerMock->expects(self::exactly(3))
            ->method('saveIndex')
            ->willReturnCallback(
                function (...$args) use ($indexNameMock, $simpleData, $bundleData, $groupedData) {
                    static $callIndex = 0;
                    $callIndex++;
                    self::assertEquals($indexNameMock, $args[0]);
                    self::assertEquals($this->connectionName, $args[2]);
                    if ($callIndex === 1) {
                        self::assertEquals(new \ArrayIterator($simpleData), $args[1]);
                    } elseif ($callIndex === 2) {
                        self::assertEquals(new \ArrayIterator($bundleData), $args[1]);
                    } elseif ($callIndex === 3) {
                        self::assertEquals(new \ArrayIterator($groupedData), $args[1]);
                    }
                }
            );

        $this->indexDataFiller->fillIndex($indexNameMock, $skuListInStockMock, $this->connectionName);
    }
}
