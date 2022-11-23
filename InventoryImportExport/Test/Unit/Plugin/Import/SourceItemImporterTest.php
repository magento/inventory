<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryImportExport\Test\Unit\Plugin\Import;

use Magento\CatalogImportExport\Model\Import\Product\SkuProcessor;
use Magento\CatalogImportExport\Model\StockItemProcessorInterface;
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
     * @var StockItemProcessorInterface|MockObject
     */
    private $stockItemProcessorMock;

    /**
     * @var SourceItemInterface|MockObject
     */
    private $sourceItemMock;

    /**
     * @var IsSingleSourceModeInterface|MockObject
     */
    private $isSingleSourceModeMock;

    /**
     * @var SkuProcessor|MockObject
     */
    private $skuProcessorMock;

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

        $this->stockItemProcessorMock = $this->getMockBuilder(StockItemProcessorInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->sourceItemMock = $this->getMockBuilder(SourceItemInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->isSingleSourceModeMock = $this->createMock(IsSingleSourceModeInterface::class);

        $this->skuProcessorMock = $this->createMock(SkuProcessor::class);

        $this->plugin = new SourceItemImporter(
            $this->sourceItemsSaveMock,
            $this->sourceItemFactoryMock,
            $this->defaultSourceMock,
            $this->isSingleSourceModeMock,
            $this->resourceConnectionMock,
            $this->skuProcessorMock
        );
    }

    /**
     * @dataProvider sourceItemDataProvider
     *
     * @param string $sku
     * @param string $sourceCode
     * @param float $quantity
     * @param int $isInStock
     * @param array $existingSkus
     * @throws CouldNotSaveException
     * @throws InputException
     * @throws ValidationException
     */
    public function testAfterImportForMultipleSource(
        string $sku,
        string $sourceCode,
        float $quantity,
        int $isInStock,
        array $existingSkus
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

        $this->saveSkusHavingDefaultSourceMock($sku);

        $this->skuProcessorMock->expects($this->once())->method('getOldSkus')->willReturn($existingSkus);
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

        if ($existingSkus) {
            $this->isSingleSourceModeMock->expects($this->atLeastOnce())->method('execute')->willReturn(false);
        }

        if ($existingSkus && !$this->isSingleSourceModeMock->execute()) {
            $this->sourceItemMock->expects($this->once())->method('getSku')->willReturn($sku);
        }
        if (!$existingSkus) {
            $this->sourceItemsSaveMock->expects($this->once())->method('execute')->with([$this->sourceItemMock])
                ->willReturnSelf();
        }

        $this->plugin->afterProcess($this->stockItemProcessorMock, '', $stockData, []);
    }

    /**
     * @param string $sku
     */
    private function saveSkusHavingDefaultSourceMock(string $sku): void
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
            ->method('fetchCol')
            ->willReturn([['sku' => $sku]]);

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
     * Source item data provider
     *
     * @return array[]
     */
    public function sourceItemDataProvider(): array
    {
        return [
            'non-default existing source code with 0 quantity for existing product' => [
                'simple', 'default', 0.0, 0, ['simple' => 'default']
            ],
            'non-default existing source code with quantity > 1 for existing product' => [
                'simple', 'default', 25.0, 1, []
            ],
            'default existing source code with 0 quantity for existing product' => [
                'simple', 'default', 0.0, 0, ['simple' => 'default']
            ],
            'default existing source code with quantity > 1 for existing product' => [
                'simple', 'default', 100.0, 1, []
            ],
            'default source code with 0 quantity for new product' => [
                'simple', 'default', 0.0, 0, []
            ],
            'default source code with quantity > 1 for new product' => [
                'simple', 'default', 100.0, 1, []
            ],
        ];
    }
}
