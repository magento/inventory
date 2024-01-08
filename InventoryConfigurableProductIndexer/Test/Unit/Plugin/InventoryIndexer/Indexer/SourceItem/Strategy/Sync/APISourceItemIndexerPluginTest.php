<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

// @codingStandardsIgnoreStart
namespace Magento\InventoryConfigurableProductIndexer\Test\Unit\Plugin\InventoryIndexer\Indexer\SourceItem\Strategy\Sync;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Type\AbstractType;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Inventory\Model\SourceItem;
use Magento\InventoryApi\Api\GetSourceItemsBySkuInterface;
use Magento\InventoryCatalogApi\Api\DefaultSourceProviderInterface;
use Magento\InventoryCatalogApi\Model\GetSkusByProductIdsInterface;
use Magento\InventoryConfigurableProductIndexer\Indexer\SourceItem\SourceItemIndexer;
use Magento\InventoryConfigurableProductIndexer\Plugin\InventoryIndexer\Indexer\SourceItem\Strategy\Sync\APISourceItemIndexerPlugin;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
// @codingStandardsIgnoreEnd

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class APISourceItemIndexerPluginTest extends TestCase
{
    /**
     * @var SourceItemIndexer|MockObject
     */
    private SourceItemIndexer $configurableProductsSourceItemIndexer;

    /**
     * @var GetSourceItemsBySkuInterface|MockObject
     */
    private GetSourceItemsBySkuInterface $getSourceItemsBySku;

    /**
     * @var DefaultSourceProviderInterface|MockObject
     */
    private DefaultSourceProviderInterface $defaultSourceProvider;

    /**
     * @var GetSkusByProductIdsInterface|MockObject
     */
    private GetSkusByProductIdsInterface $skuProvider;

    /**
     * @var APISourceItemIndexerPlugin|MockObject
     */
    private APISourceItemIndexerPlugin $plugin;

    protected function setUp(): void
    {
        $this->configurableProductsSourceItemIndexer = $this->createMock(SourceItemIndexer::class);
        $this->getSourceItemsBySku = $this->createMock(GetSourceItemsBySkuInterface::class);
        $this->defaultSourceProvider = $this->createMock(DefaultSourceProviderInterface::class);
        $this->skuProvider = $this->createMock(GetSkusByProductIdsInterface::class);

        $this->plugin = new APISourceItemIndexerPlugin(
            $this->configurableProductsSourceItemIndexer,
            $this->getSourceItemsBySku,
            $this->defaultSourceProvider,
            $this->skuProvider
        );

        parent::setUp();
    }

    public function testAfterSave()
    {
        $subject = $this->createMock(ProductResource::class);
        $result = $this->createMock(ProductResource::class);
        $object = $this->createMock(Product::class);
        $object->expects($this->once())->method('getTypeId')->willReturn(Configurable::TYPE_CODE);
        $typeInstance = $this->createMock(AbstractType::class);
        $typeInstance->expects($this->once())
            ->method('getChildrenIds')
            ->with(1)
            ->willReturn(
                [
                    0 => [11 => '11', 12 => '12']
                ]
            );

        $object->expects($this->once())->method('getTypeInstance')->willReturn($typeInstance);
        $object->expects($this->once())->method('getId')->willReturn(1);
        $object->expects($this->once())->method('cleanModelCache');
        $this->defaultSourceProvider->expects($this->exactly(2))->method('getCode')->willreturn('default');
        $childSourceItem1 = $this->getSourceItem(1);
        $childSourceItem2 = $this->getSourceItem(2);
        $this->skuProvider->expects($this->once())
            ->method('execute')
            ->with([11 => '11', 12 => '12'])
            ->willReturn([11 => 'child-1', 12 => 'child-2']);
        $this->getSourceItemsBySku->expects($this->exactly(2))
            ->method('execute')
            ->withConsecutive(['child-1'], ['child-2'])
            ->willReturnOnConsecutiveCalls([$childSourceItem1], [$childSourceItem2]);
        $this->configurableProductsSourceItemIndexer->expects($this->once())->method('executeList')->with([1, 2]);

        $interceptorResult = $this->plugin->afterSave($subject, $result, $object);
        $this->assertSame($interceptorResult, $result);
    }

    private function getSourceItem(int $returnValue): MockObject
    {
        $sourceItem = $this->createMock(SourceItem::class);
        $sourceItem->expects($this->once())->method('getSourceCode')->willReturn('non-default-source');
        $sourceItem->expects($this->once())->method('getId')->willReturn($returnValue);

        return $sourceItem;
    }
}
