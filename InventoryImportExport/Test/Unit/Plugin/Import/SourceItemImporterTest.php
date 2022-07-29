<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryImportExport\Test\Unit\Plugin\Import;

use Magento\CatalogImportExport\Model\StockItemImporterInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Validation\ValidationException;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterfaceFactory;
use Magento\InventoryApi\Api\GetSourceItemsBySkuInterface;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;
use Magento\InventoryCatalogApi\Model\IsSingleSourceModeInterface;
use Magento\InventoryCatalogApi\Api\DefaultSourceProviderInterface;
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
     * @var GetSourceItemsBySkuInterface|MockObject
     */
    private $getSourceItemsBySkuMock;

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
        $this->getSourceItemsBySkuMock = $this->getMockBuilder(GetSourceItemsBySkuInterface::class)
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
            $this->getSourceItemsBySkuMock,
            $this->isSingleSourceModeMock
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
        string $existingSourceCode,
        string $sourceCode,
        float $quantity,
        int $isInStock
    ): void {
        $stockData = [
            'simple' => [
                'qty' => $quantity,
                'is_in_stock' => $isInStock,
                'product_id' => 1,
                'website_id' => 0,
                'stock_id' => 1,
            ]
        ];

        $this->defaultSourceMock->expects($this->once())->method('getCode')->willReturn($sourceCode);
        $this->sourceItemMock->expects($this->once())->method('setSku')->with('simple')
            ->willReturnSelf();
        $this->sourceItemMock->expects($this->once())->method('setSourceCode')->with($sourceCode)
            ->willReturnSelf();
        $this->sourceItemMock->expects($this->once())->method('setQuantity')->with($quantity)
            ->willReturnSelf();
        $this->sourceItemMock->expects($this->once())->method('setStatus')->with($isInStock)
            ->willReturnSelf();
        $this->sourceItemFactoryMock->expects($this->once())->method('create')
            ->willReturn($this->sourceItemMock);
        $this->sourceItemMock->expects($this->once())->method('getSku')->willReturn('simple');

        $exitingSourceItemMock = $this->getMockBuilder(SourceItemInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $exitingSourceItemMock->expects($this->once())->method('getSourceCode')
            ->willReturn($existingSourceCode);
        $this->getSourceItemsBySkuMock->expects($this->once())->method('execute')->with('simple')
            ->willReturn([$exitingSourceItemMock]);

        $this->sourceItemMock->expects($this->any())->method('getSourceCode')->willReturn($sourceCode);
        $this->sourceItemMock->expects($this->once())->method('getQuantity')->willReturn($quantity);

        $this->sourceItemsSaveMock->expects($this->any())->method('execute')->with([$this->sourceItemMock])
            ->willReturnSelf();

        $this->plugin->afterImport($this->stockItemImporterMock, '', $stockData);
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
                'source-code-1', 'default', 0.0, 0
            ],
            'non-default existing source code with quantity > 1' => [
                'source-code-1', 'default', 25.0, 1
            ],
            'default existing source code with 0 quantity' => [
                'default', 'default', 0.0, 0
            ],
            'default existing source code with quantity > 1' => [
                'default', 'default', 100.0, 1
            ],
        ];
    }
}
