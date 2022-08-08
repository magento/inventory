<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryImportExport\Test\Unit\Plugin\Import;

use Magento\CatalogImportExport\Model\StockItemImporterInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Validation\ValidationException;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterfaceFactory;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;
use Magento\InventoryCatalogApi\Api\DefaultSourceProviderInterface;
use Magento\InventoryCatalogApi\Model\IsSingleSourceModeInterface;
use Magento\InventoryImportExport\Plugin\Import\SourceItemImporter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Tests for SourceItemImporter class.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SourceItemImporterTest extends TestCase
{
    /**
     * @var SourceItemsSaveInterface|MockObject
     */
    private $sourceItemsSaveMock;

    /**
     * @var SourceItemInterfaceFactory|MockObject
     */
    private $sourceItemFactoryMock;

    /**
     * @var DefaultSourceProviderInterface|MockObject
     */
    private $defaultSourceMock;

    /**
     * @var ResourceConnection|MockObject
     */
    private $resourceConnectionMock;

    /**
     * @var SourceItemImporter
     */
    private $plugin;

    /**
     * @var StockItemImporterInterface|MockObject
     */
    private $stockItemImporterMock;

    /**
     * @var SourceItemInterface|MockObject
     */
    private $sourceItemMock;

    /**
     * @var IsSingleSourceModeInterface|MockObject
     */
    private $isSingleSourceModeMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->sourceItemsSaveMock = $this->getMockBuilder(SourceItemsSaveInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->sourceItemFactoryMock = $this->createMock(SourceItemInterfaceFactory::class);
        $this->defaultSourceMock = $this->getMockBuilder(DefaultSourceProviderInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resourceConnectionMock = $this->getMockBuilder(ResourceConnection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->stockItemImporterMock = $this->getMockBuilder(StockItemImporterInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->sourceItemMock = $this->getMockBuilder(SourceItemInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->isSingleSourceModeMock = $this->createMock(IsSingleSourceModeInterface::class);

        $this->plugin = new SourceItemImporter(
            $this->sourceItemsSaveMock,
            $this->sourceItemFactoryMock,
            $this->defaultSourceMock,
            $this->isSingleSourceModeMock,
            $this->resourceConnectionMock
        );
    }

    /**
     * @dataProvider sourceItemDataProvider
     *
     * @param string $existingSourceCode
     * @param string $sourceCode
     * @param float $quantity
     * @param int $isInStock
     * @throws CouldNotSaveException
     * @throws InputException
     * @throws ValidationException
     */
    public function testAfterImportForMultipleSource(
        string $sku,
        string $existingSourceCode,
        string $sourceCode,
        float $quantity,
        int $isInStock
    ): void {
        $stockData = [
            $sku => [
                'qty' => $quantity,
                'is_in_stock' => $isInStock,
                'product_id' => 1,
                'website_id' => 0,
                'stock_id' => 1,
            ]
        ];

        $this->saveSourceRelationMock($existingSourceCode, $sku);

        $this->defaultSourceMock->expects($this->exactly(2))->method('getCode')->willReturn($sourceCode);
        $this->sourceItemMock->expects($this->once())->method('setSku')->with($sku)
            ->willReturnSelf();
        $this->sourceItemMock->expects($this->once())->method('setSourceCode')->with($sourceCode)
            ->willReturnSelf();
        $this->sourceItemMock->expects($this->once())->method('setQuantity')->with($quantity)
            ->willReturnSelf();
        $this->sourceItemMock->expects($this->once())->method('setStatus')->with($isInStock)
            ->willReturnSelf();
        $this->sourceItemFactoryMock->expects($this->once())->method('create')
            ->willReturn($this->sourceItemMock);


        $this->isSingleSourceModeMock->expects($this->once())->method('execute')->willReturn(false);

        $this->isSourceItemAllowedMock($sku, $sourceCode, $quantity);

        $this->plugin->afterImport($this->stockItemImporterMock, '', $stockData);
    }

    /**
     * @param string $existingSourceCode
     * @param string $sku
     */
    private function saveSourceRelationMock(string $existingSourceCode, string $sku): void
    {
        $connectionAdapterMock = $this->getMockForAbstractClass(AdapterInterface::class);
        $selectMock = $this->createMock(Select::class);

        $connectionAdapterMock->expects($this->once())
            ->method('select')
            ->willReturn($selectMock);
        $selectMock->expects($this->once())
            ->method('from')
            ->willReturnSelf();
        $selectMock->expects($this->exactly(2))
            ->method('where')
            ->willReturnSelf();
        $connectionAdapterMock->expects($this->once())
            ->method('fetchPairs')
            ->willReturn([['sku' => $sku, 'source_code' => $existingSourceCode]]);

        $this->resourceConnectionMock
            ->expects($this->once())
            ->method('getConnection')
            ->willReturn($connectionAdapterMock);

        $this->resourceConnectionMock
            ->expects($this->once())
            ->method('getTableName')
            ->willReturnSelf();
    }

    /**
     * @param string $sku
     * @param string $sourceCode
     * @param float $quantity
     */
    private function isSourceItemAllowedMock(string $sku, string $sourceCode, float $quantity): void
    {
        $this->sourceItemMock->expects($this->once())->method('setSku')->with($sku)
            ->willReturnSelf();

        $this->sourceItemMock->expects($this->any())->method('getSourceCode')->willReturn($sourceCode);
        $this->sourceItemMock->expects($this->once())->method('getQuantity')->willReturn($quantity);
        $this->sourceItemMock->expects($this->any())->method('getSku')->willReturn($sku);

        $this->sourceItemsSaveMock->expects($this->any())->method('execute')->with([$this->sourceItemMock])
            ->willReturnSelf();
    }

    /**
     * Source item data provider
     *
     * @return array[]
     */
    public function sourceItemDataProvider(): array
    {
        return [
            'non-default existing source code with 0 quantity' => [
                'simple', 'source-code-1', 'default', 0.0, 0
            ],
            'non-default existing source code with quantity > 1' => [
                'simple', 'source-code-1', 'default', 25.0, 1
            ],
            'default existing source code with 0 quantity' => [
                'simple', 'default', 'default', 0.0, 0
            ],
            'default existing source code with quantity > 1' => [
                'simple', 'default', 'default', 100.0, 1
            ],
        ];
    }
}
