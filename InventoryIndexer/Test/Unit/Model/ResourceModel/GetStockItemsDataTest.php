<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Test\Unit\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\Exception\LocalizedException;
use Magento\InventoryCatalogApi\Api\DefaultStockProviderInterface;
use Magento\InventoryIndexer\Model\ResourceModel\GetStockItemsData;
use Magento\InventoryIndexer\Model\ResourceModel\StockItemDataHandler;
use Magento\InventoryIndexer\Model\StockIndexTableNameResolverInterface;
use Magento\InventorySalesApi\Model\GetStockItemsDataInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;

class GetStockItemsDataTest extends TestCase
{
    private const STOCK_ID = 1;

    /**
     * @var ResourceConnection|MockObject
     */
    private ResourceConnection $resourceMock;

    /**
     * @var AdapterInterface|MockObject
     */
    private AdapterInterface $connectionMock;

    /**
     * @var Select|Stub
     */
    private Select $selectMock;

    /**
     * @var StockIndexTableNameResolverInterface|Stub
     */
    private StockIndexTableNameResolverInterface $stockIndexTableNameResolverMock;

    /**
     * @var DefaultStockProviderInterface|Stub
     */
    private DefaultStockProviderInterface $defaultStockProviderMock;

    /**
     * @var StockItemDataHandler|Stub
     */
    private StockItemDataHandler $stockItemDataHandlerMock;

    /**
     * @var GetStockItemsData
     */
    private GetStockItemsData $getStockItemsData;

    /**
     * @return void
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    protected function setUp(): void
    {
        $this->resourceMock = $this->createMock(ResourceConnection::class);
        $this->connectionMock = $this->createMock(AdapterInterface::class);
        $this->selectMock = $this->createStub(Select::class);
        $this->stockIndexTableNameResolverMock = $this->createStub(StockIndexTableNameResolverInterface::class);
        $this->defaultStockProviderMock = $this->createStub(DefaultStockProviderInterface::class);
        $this->stockItemDataHandlerMock = $this->createStub(StockItemDataHandler::class);

        $this->resourceMock->method('getConnection')->willReturn($this->connectionMock);
        $this->resourceMock->method('getTableName')->willReturnArgument(0);
        $this->connectionMock->method('select')->willReturn($this->selectMock);
        $this->selectMock->method('from')->willReturnSelf();
        $this->selectMock->method('join')->willReturnSelf();
        $this->selectMock->method('where')->willReturnSelf();

        $this->getStockItemsData = new GetStockItemsData(
            $this->resourceMock,
            $this->stockIndexTableNameResolverMock,
            $this->defaultStockProviderMock,
            $this->stockItemDataHandlerMock
        );
    }

    /**
     *  Ensure fetchAll() is invoked with the SKU list bound as the second (binding) parameter,
     *  both for the default stock (cataloginventory_stock_status) and a custom stock (index table) query.
     *
     * @param int $defaultStockId
     * @param int $requestedStockId
     * @return void
     * @throws LocalizedException
     */
    #[DataProvider('stockIdDataProvider')]
    public function testExecuteCallsFetchAllWithBindingParameter(int $defaultStockId, int $requestedStockId): void
    {
        $skus = ['sku1', 'sku2'];

        $this->defaultStockProviderMock->method('getId')->willReturn($defaultStockId);
        $this->stockIndexTableNameResolverMock->method('execute')->willReturn('inventory_stock_1');

        $this->connectionMock->expects($this->once())
            ->method('fetchAll')
            ->with(
                $this->identicalTo($this->selectMock),
                $this->equalTo(self::buildExpectedBind($skus))
            )
            ->willReturn([
                ['sku' => 'sku1', 'quantity' => 5, 'is_salable' => 1],
                ['sku' => 'sku2', 'quantity' => 0, 'is_salable' => 0],
            ]);

        $result = $this->getStockItemsData->execute($skus, $requestedStockId);

        $this->assertSame(
            [
                GetStockItemsDataInterface::QUANTITY => 5,
                GetStockItemsDataInterface::IS_SALABLE => 1,
            ],
            $result['sku1']
        );
        $this->assertSame(
            [
                GetStockItemsDataInterface::QUANTITY => 0,
                GetStockItemsDataInterface::IS_SALABLE => 0,
            ],
            $result['sku2']
        );
    }

    /**
     * @return array
     */
    public static function stockIdDataProvider(): array
    {
        return [
            'default stock' => [self::STOCK_ID, self::STOCK_ID],
            'custom stock' => [self::STOCK_ID, self::STOCK_ID + 1],
        ];
    }

    /**
     * Build the expected fetchAll() binding array the way GetStockItemsData::execute() does:
     * each SKU gets its own indexed placeholder (sku0, sku1, ...).
     *
     * @param string[] $skus
     * @return array
     */
    private static function buildExpectedBind(array $skus): array
    {
        $keys = array_map(static fn (int $i): string => 'sku' . $i, array_keys($skus));

        return array_combine($keys, $skus);
    }

    /**
     * @return void
     * @throws LocalizedException
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testExecuteFallsBackToStockItemTableWhenFetchAllReturnsNoRows(): void
    {
        $skus = ['sku1'];

        $this->defaultStockProviderMock->method('getId')->willReturn(self::STOCK_ID);

        $this->connectionMock->expects($this->once())
            ->method('fetchAll')
            ->with(
                $this->identicalTo($this->selectMock),
                $this->equalTo(self::buildExpectedBind($skus))
            )
            ->willReturn([]);

        $fallbackRow = ['quantity' => 3, 'is_salable' => 1];
        $stockItemDataHandlerMock = $this->createMock(StockItemDataHandler::class);
        $stockItemDataHandlerMock->expects($this->once())
            ->method('getStockItemDataFromStockItemTable')
            ->with('sku1', self::STOCK_ID)
            ->willReturn($fallbackRow);

        $getStockItemsData = new GetStockItemsData(
            $this->resourceMock,
            $this->stockIndexTableNameResolverMock,
            $this->defaultStockProviderMock,
            $stockItemDataHandlerMock
        );

        $result = $getStockItemsData->execute($skus, self::STOCK_ID);

        $this->assertSame(['sku1' => $fallbackRow], $result);
    }

    /**
     * @return void
     * @throws LocalizedException
     */
    public function testExecuteWrapsConnectionExceptionInLocalizedException(): void
    {
        $skus = ['sku1'];

        $this->defaultStockProviderMock->method('getId')->willReturn(self::STOCK_ID);

        $this->connectionMock->expects($this->once())
            ->method('fetchAll')
            ->with(
                $this->identicalTo($this->selectMock),
                $this->equalTo(self::buildExpectedBind($skus))
            )
            ->willThrowException(new \Exception('DB error'));

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Could not receive Stock Item data');

        $this->getStockItemsData->execute($skus, self::STOCK_ID);
    }

    /**
     * @return void
     * @throws LocalizedException
     */
    public function testWithEmptySkus(): void
    {
        $this->resourceMock->expects($this->never())->method('getConnection');
        $this->assertEmpty($this->getStockItemsData->execute([], self::STOCK_ID));
    }
}
