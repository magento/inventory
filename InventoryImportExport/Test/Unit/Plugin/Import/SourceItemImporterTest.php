<?php
/**
 * Copyright 2022 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryImportExport\Test\Unit\Plugin\Import;

use Magento\CatalogImportExport\Model\Import\Product\SkuStorage;
use Magento\CatalogImportExport\Model\StockItemProcessorInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Validation\ValidationException;
use Magento\Inventory\Model\ResourceModel\SourceItem;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterfaceFactory;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;
use Magento\InventoryCatalogApi\Api\DefaultSourceProviderInterface;
use Magento\InventoryCatalogApi\Model\GetProductTypesBySkusInterface;
use Magento\InventoryCatalogApi\Model\IsSingleSourceModeInterface;
use Magento\InventoryConfigurationApi\Model\IsSourceItemManagementAllowedForProductTypeInterface;
use Magento\InventoryImportExport\Plugin\Import\SourceItemImporter;
use Magento\InventoryIndexer\Indexer\CompositeProductsIndexer;
use Magento\InventoryIndexer\Indexer\SourceItem\SourceItemIndexer;
use PHPUnit\Framework\Attributes\DataProvider;
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
    private $sourceItemResourceModelMock;

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
     * @var CompositeProductsIndexer|MockObject
     */
    private $compositeProductsIndexerMock;

    /**
     * @var SkuStorage|MockObject
     */
    private SkuStorage $skuStorageMock;

    /**
     * @var GetProductTypesBySkusInterface|MockObject
     */
    private $getProductTypesBySkusMock;

    /**
     * @var IsSourceItemManagementAllowedForProductTypeInterface|MockObject
     */
    private $isSourceItemManagementAllowedForProductTypeMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->sourceItemsSaveMock = $this->createMock(SourceItemsSaveInterface::class);
        $this->sourceItemFactoryMock = $this->createMock(SourceItemInterfaceFactory::class);
        $this->defaultSourceMock = $this->createMock(DefaultSourceProviderInterface::class);
        $this->sourceItemResourceModelMock = $this->createMock(SourceItem::class);
        $this->stockItemProcessorMock = $this->createMock(StockItemProcessorInterface::class);
        $this->sourceItemMock = $this->createMock(SourceItemInterface::class);
        $this->isSingleSourceModeMock = $this->createMock(IsSingleSourceModeInterface::class);
        $this->compositeProductsIndexerMock = $this->createMock(CompositeProductsIndexer::class);

        $this->skuStorageMock = $this->createMock(SkuStorage::class);

        $this->getProductTypesBySkusMock = $this->createMock(GetProductTypesBySkusInterface::class);
        $this->getProductTypesBySkusMock->method('execute')
            ->willReturnCallback(fn (array $skus) => array_fill_keys($skus, 'simple'));
        $this->isSourceItemManagementAllowedForProductTypeMock = $this->createMock(
            IsSourceItemManagementAllowedForProductTypeInterface::class
        );
        $this->isSourceItemManagementAllowedForProductTypeMock->method('execute')
            ->willReturnCallback(fn (string $type) => $type === 'simple');

        $this->plugin = new SourceItemImporter(
            $this->sourceItemsSaveMock,
            $this->sourceItemFactoryMock,
            $this->defaultSourceMock,
            $this->isSingleSourceModeMock,
            $this->skuStorageMock,
            $this->sourceItemResourceModelMock,
            $this->createMock(SourceItemIndexer::class),
            $this->compositeProductsIndexerMock,
            $this->getProductTypesBySkusMock,
            $this->isSourceItemManagementAllowedForProductTypeMock,
        );
    }

    /**
     * @param string $sku
     * @param string $sourceCode
     * @param float $quantity
     * @param int $isInStock
     * @param array $existingSkus
     * @throws CouldNotSaveException
     * @throws InputException
     * @throws ValidationException
     */
    #[DataProvider('sourceItemDataProvider')]
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

        $this->sourceItemResourceModelMock->expects($this->once())
            ->method('findAllBySkus')
            ->willReturn([['sku' => $sku, 'source_code' => 'default', 'source_item_id' => 1]]);

        $this->skuStorageMock->method('get')->willReturnCallback(function ($sku) use ($existingSkus) {
            $skuLowered = strtolower($sku);

            return $existingSkus[$skuLowered] ?? null;
        });

        $this->skuStorageMock->method('has')->willReturnCallback(function ($sku) use ($existingSkus) {
            $skuLowered = strtolower($sku);

            return isset($existingSkus[$skuLowered]);
        });

        $this->defaultSourceMock->expects($this->once())->method('getCode')->willReturn($sourceCode);
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

        $this->sourceItemMock->expects($this->any())->method('getSku')->willReturn($sku);

        if (!$existingSkus) {
            $this->sourceItemsSaveMock->expects($this->once())->method('execute')->with([$this->sourceItemMock])
                ->willReturnSelf();
        }
        $this->compositeProductsIndexerMock->expects($this->once())->method('reindexList')->with([$sku]);

        $this->plugin->afterProcess($this->stockItemProcessorMock, '', $stockData, []);
    }

    /**
     * Composite product types (configurable, bundle, grouped) don't support source items: the
     * import must not create inventory_source_item rows for them, but they must still take part
     * in the composite products reindex.
     *
     * @return void
     */
    public function testAfterImportSkipsSourceItemsForCompositeProductTypes(): void
    {
        $stockData = [
            'configurable-sku' => ['qty' => 0, 'is_in_stock' => 1, 'product_id' => 1],
            'simple-sku' => ['qty' => 10, 'is_in_stock' => 1, 'product_id' => 2],
        ];

        $this->getProductTypesBySkusMock = $this->createMock(GetProductTypesBySkusInterface::class);
        $this->getProductTypesBySkusMock->method('execute')
            ->willReturn(['configurable-sku' => 'configurable', 'simple-sku' => 'simple']);
        $this->plugin = new SourceItemImporter(
            $this->sourceItemsSaveMock,
            $this->sourceItemFactoryMock,
            $this->defaultSourceMock,
            $this->isSingleSourceModeMock,
            $this->skuStorageMock,
            $this->sourceItemResourceModelMock,
            $this->createMock(SourceItemIndexer::class),
            $this->compositeProductsIndexerMock,
            $this->getProductTypesBySkusMock,
            $this->isSourceItemManagementAllowedForProductTypeMock,
        );

        $this->isSingleSourceModeMock->method('execute')->willReturn(true);
        $this->skuStorageMock->method('has')->willReturn(false);
        $this->defaultSourceMock->method('getCode')->willReturn('default');
        $this->sourceItemMock->method('setSku')->willReturnSelf();
        $this->sourceItemMock->method('setSourceCode')->willReturnSelf();
        $this->sourceItemMock->method('setQuantity')->willReturnSelf();
        $this->sourceItemMock->method('setStatus')->willReturnSelf();

        // Only the simple product produces a source item.
        $this->sourceItemFactoryMock->expects($this->once())->method('create')
            ->willReturn($this->sourceItemMock);
        $this->sourceItemMock->expects($this->once())->method('setSku')->with('simple-sku');
        $this->sourceItemsSaveMock->expects($this->once())->method('execute')
            ->with([$this->sourceItemMock]);
        // Both SKUs still take part in the composite reindex.
        $this->compositeProductsIndexerMock->expects($this->once())->method('reindexList')
            ->with(['configurable-sku', 'simple-sku']);

        $this->plugin->afterProcess($this->stockItemProcessorMock, '', $stockData, []);
    }

    /**
     * Source item data provider
     *
     * @return array[]
     */
    public static function sourceItemDataProvider(): array
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
